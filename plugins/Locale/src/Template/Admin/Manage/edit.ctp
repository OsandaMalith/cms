<?php
/**
 * Licensed under The GPL-3.0 License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @since    2.0.0
 * @author   Christopher Castro <chris@quickapps.es>
 * @link     http://www.quickappscms.org
 * @license  http://opensource.org/licenses/gpl-3.0.html GPL-3.0 License
 */
?>

<?php echo $this->Form->create($language); ?>
    <fieldset>
        <legend><?php echo __d('locale', 'Editing Language'); ?></legend>

        <?php echo $this->Form->input('name', ['label' => __d('locale', 'Language Name')]); ?>
        <?php
            echo $this->Form->input('direction', [
                'type' => 'select',
                'options' => [
                    'ltr' => __d('locale', 'Left to Right'),
                    'rtl' => __d('locale', 'Right to Left'),
                ],
                'label' => __d('locale', 'Writing Direction'),
            ]);
        ?>

        <div class="input-group">
            <span class="input-group-addon"><?php echo __d('locale', 'Language Icon'); ?>: <span class="flag"></span></span>
            <?php echo $this->Form->input('icon', ['type' => 'select', 'options' => $icons, 'label' => false, 'onchange' => 'changeFlag();', 'id' => 'flag-icons']); ?>
        </div>

        <p>&nbsp;</p>

        <?php echo $this->Form->submit(__d('locale', 'Save')); ?>
    </fieldset>
<?php echo $this->Form->end(); ?>

<script> var baseURL = '<?php echo $this->Url->build('/Locale/img/flags/', true); ?>'; </script>
<?php echo $this->Html->script('Locale.language.form.js'); ?>