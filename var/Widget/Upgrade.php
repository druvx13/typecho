<?php

namespace Widget;

use Typecho\Common;
use Exception;
use Widget\Base\Options as BaseOptions;
use Utils\Upgrade as UpgradeAction;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * Upgrade widget
 *
 * @author qining
 * @category typecho
 * @package Widget
 */
class Upgrade extends BaseOptions implements ActionInterface
{
    /**
     * minimum supported version
     */
    public const MIN_VERSION = '1.1.0';

    /**
     * Execute upgrade routine
     *
     * @throws \Typecho\Db\Exception
     */
    public function upgrade()
    {
        $currentVersion = $this->options->version;

        if (version_compare($currentVersion, self::MIN_VERSION, '<')) {
            Notice::alloc()->set(
                _t('请先升级至版本 %s', self::MIN_VERSION),
                'error'
            );

            $this->response->goBack();
        }

        $ref = new \ReflectionClass(UpgradeAction::class);
        $message = [];

        foreach ($ref->getMethods() as $method) {
            preg_match("/^v([_0-9]+)$/", $method->getName(), $matches);
            $version = str_replace('_', '.', $matches[1]);

            if (version_compare($currentVersion, $version, '>=')) {
                continue;
            }

            $options = Options::allocWithAlias($version);

            /** 执行升级脚本 */
            try {
                $result = $method->invoke(null, $this->db, $options);
                if (!empty($result)) {
                    $message[] = $result;
                }
            } catch (Exception $e) {
                Notice::alloc()->set($e->getMessage(), 'error');
                $this->response->goBack();
            }

            /** Update version number */
            $this->update(
                ['value' => 'Typecho ' . $version],
                $this->db->sql()->where('name = ?', 'generator')
            );

            Options::destroy($version);
        }

        /** Update version number */
        $this->update(
            ['value' => 'Typecho ' . Common::VERSION],
            $this->db->sql()->where('name = ?', 'generator')
        );

        Notice::alloc()->set(
            empty($message) ? _t("Upgrade completed.") : $message,
            empty($message) ? 'success' : 'notice'
        );
    }

    /**
     * Initialization function
     *
     * @throws \Typecho\Db\Exception
     * @throws \Typecho\Widget\Exception
     */
    public function action()
    {
        $this->user->pass('administrator');
        $this->security->protect();
        $this->on($this->request->isPost())->upgrade();
        $this->response->redirect($this->options->adminUrl);
    }
}
