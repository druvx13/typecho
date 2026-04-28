<?php if(!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<ul class="typecho-option">
    <li>
        <label class="typecho-label" for="dbHost"><?php _e('Database path'); ?></label>
        <input type="text" class="text" name="dbHost" id="dbHost" value="localhost"/>
        <p class="description"><?php _e('You might need use "%s"', 'localhost'); ?></p>
    </li>
</ul>
<ul class="typecho-option">
    <li>
        <label class="typecho-label" for="dbUser"><?php _e('Database username'); ?></label>
        <input type="text" class="text" name="dbUser" id="dbUser" value="postgres" />
        <p class="description"><?php _e('You might need use "%s"', 'postgres'); ?></p>
    </li>
</ul>
<ul class="typecho-option">
    <li>
        <label class="typecho-label" for="dbPassword"><?php _e('Database password'); ?></label>
        <input type="password" class="text" name="dbPassword" id="dbPassword" value="" />
    </li
</ul>
<ul class="typecho-option">
    <li>
        <label class="typecho-label" for="dbDatabase"><?php _e('Database name.'); ?></label>
        <input type="text" class="text" name="dbDatabase" id="dbDatabase" value="" />
        <p class="description"><?php _e('Please specify the database name.'); ?></p>
    </li
</ul>


<details>
    <summary>
        <strong><?php _e('Advance options'); ?></strong>
    </summary>
    <ul class="typecho-option">
        <li>
            <label class="typecho-label" for="dbPort"><?php _e('Database port'); ?></label>
            <input type="text" class="text" name="dbPort" id="dbPort" value="5432"/>
            <p class="description"><?php _e('If you do not understand this option, please keep the default setting.'); ?></p>
        </li>
    </ul>

    <input type="hidden" name="dbCharset" value="utf8" />

    <ul class="typecho-option">
        <li>
            <label class="typecho-label" for="dbSslVerify"><?php _e('Enable database SSL server-side certificate verification'); ?></label>
            <select name="dbSslVerify" id="dbSslVerify">
                <option value="off"><?php _e('Disable.'); ?></option>
                <option value="on"><?php _e('Enable.'); ?></option>
            </select>
        </li>
    </ul>
</details>
