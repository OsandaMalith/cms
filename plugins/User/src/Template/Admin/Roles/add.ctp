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

<p><?php echo $this->element('User.index_submenu'); ?></p>

<?php echo $this->Form->create($role); ?>
    <fieldset>
        <legend><?php echo __d('user', 'Register New Role'); ?></legend>
        <?php echo $this->Form->input('name', ['type' => 'text', 'label' => 'Role Name']); ?>
        <?php echo $this->Form->submit(__d('user', 'Save')); ?>
    </fieldset>
<?php echo $this->Form->end(); ?>
