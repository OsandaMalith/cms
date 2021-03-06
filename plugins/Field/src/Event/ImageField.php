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
namespace Field\Event;

use Cake\Event\Event;
use Cake\Filesystem\File;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use Cake\Validation\Validator;
use Field\BaseHandler;
use Field\Model\Entity\Field;
use Field\Utility\ImageToolbox;

/**
 * Image Field Handler.
 *
 * This field allows attach images to entities.
 */
class ImageField extends BaseHandler
{

    /**
     * {@inheritDoc}
     */
    public function entityDisplay(Event $event, Field $field, $options = [])
    {
        $View = $event->subject();
        if ($field->metadata->settings['multi'] === 'custom') {
            $settings = $field->metadata->settings;
            $settings['multi'] = $field->metadata->settings['multi_custom'];
            $field->metadata->set('settings', $settings);
        }
        return $View->element('Field.ImageField/display', compact('field', 'options'));
    }

    /**
     * {@inheritDoc}
     */
    public function entityEdit(Event $event, Field $field, $options = [])
    {
        $View = $event->subject();
        return $View->element('Field.ImageField/edit', compact('field', 'options'));
    }

    /**
     * {@inheritDoc}
     */
    public function entityFieldAttached(Event $event, Field $field)
    {
        $extra = (array)$field->extra;
        if (!empty($extra)) {
            $newExtra = [];
            foreach ($extra as $file) {
                $newExtra[] = array_merge([
                    'mime_icon' => '',
                    'file_name' => '',
                    'file_size' => '',
                    'title' => '',
                    'alt' => '',
                ], (array)$file);
            }
            $field->set('extra', $newExtra);
        }
    }

    /**
     * {@inheritDoc}
     *
     * - extra: Holds a list (array) of files and their in formation (mime-icon,
     *   file name, etc).
     *
     * - value: Holds a text containing all file names separated by space.
     */
    public function entityBeforeSave(Event $event, Field $field, $options)
    {
        // FIX Removes the "dummy" input from extra if exists, the "dummy" input is
        // used to force Field Handler to work when empty POST information is sent
        $extra = [];
        foreach ((array)$field->extra as $k => $v) {
            if (is_integer($k)) {
                $extra[] = $v;
            }
        }
        $field->set('extra', $extra);

        $files = (array)$options['_post'];
        if (!empty($files)) {
            $value = [];
            foreach ($files as $k => $file) {
                if (!is_integer($k)) {
                    unset($files[$k]);
                    continue;
                } else {
                    $file = array_merge([
                        'mime_icon' => '',
                        'file_name' => '',
                        'file_size' => '',
                        'title' => '',
                        'alt' => '',
                    ], (array)$file);
                }
                $value[] = trim("{$file['file_name']} {$file['title']} {$file['alt']}");
            }
            $field->set('value', implode(' ', $value));
            $field->set('extra', $files);
        }

        if ($field->metadata->value_id) {
            $newFileNames = Hash::extract($files, '{n}.file_name');
            $prevFiles = (array)TableRegistry::get('Field.FieldValues')
                ->get($field->metadata->value_id)
                ->extra;

            foreach ($prevFiles as $f) {
                if (!in_array($f['file_name'], $newFileNames)) {
                    $file = normalizePath(WWW_ROOT . "/files/{$field->settings['upload_folder']}/{$f['file_name']}", DS);
                    $file = new File($file);
                    $file->delete();
                }
            }
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function entityValidate(Event $event, Field $field, Validator $validator)
    {
        if ($field->metadata->required) {
            $validator
                ->add($field->name, 'isRequired', [
                    'rule' => function ($value, $context) use ($field) {
                        if (isset($context['data'][$field->name])) {
                            $count = 0;
                            foreach ($context['data'][$field->name] as $k => $file) {
                                if (is_integer($k)) {
                                    $count++;
                                }
                            }
                            return $count > 0;
                        }
                        return false;
                    },
                    'message' => __d('field', 'You must upload one image at least.')
                ]);
        }

        if ($field->metadata->settings['multi'] !== 'custom') {
            $maxFiles = intval($field->metadata->settings['multi']);
        } else {
            $maxFiles = intval($field->metadata->settings['multi_custom']);
        }

        $validator
            ->add($field->name, 'numberOfFiles', [
                'rule' => function ($value, $context) use ($field, $maxFiles) {
                    if (isset($context['data'][$field->name])) {
                        $count = 0;
                        foreach ($context['data'][$field->name] as $k => $file) {
                            if (is_integer($k)) {
                                $count++;
                            }
                        }

                        return $count <= $maxFiles;
                    }
                    return false;
                },
                'message' => __d('field', 'You can upload {0} images as maximum.', $maxFiles)
            ]);

        if (!empty($field->metadata->settings['extensions'])) {
            $extensions = $field->metadata->settings['extensions'];
            $extensions = array_map('strtolower', array_map('trim', explode(',', $extensions)));
            $validator
                ->add($field->name, 'extensions', [
                    'rule' => function ($value, $context) use ($field, $extensions) {
                        if (isset($context['data'][$field->name])) {
                            foreach ($context['data'][$field->name] as $k => $file) {
                                if (is_integer($k)) {
                                    $ext = strtolower(str_replace('.', '', strrchr($file['file_name'], '.')));
                                    if (!in_array($ext, $extensions)) {
                                        return false;
                                    }
                                }
                            }
                            return true;
                        }
                        return false;
                    },
                    'message' => __d('field', 'Invalid image extension. Allowed extension are: {0}', $field->metadata->settings['extensions'])
                ]);
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function entityAfterDelete(Event $event, Field $field, $options)
    {
        foreach ((array)$field->extra as $image) {
            if (!empty($image['file_name'])) {
                ImageToolbox::delete(WWW_ROOT . "/files/{$field->settings['upload_folder']}/{$image['file_name']}");
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function instanceInfo(Event $event)
    {
        return [
            'type' => 'text',
            'name' => __d('field', 'Image'),
            'description' => __d('field', 'Allows to attach image files to contents.'),
            'hidden' => false,
            'maxInstances' => 0,
            'searchable' => false,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function instanceSettingsForm(Event $event, $instance, $options = [])
    {
        $View = $event->subject();
        return $View->element('Field.ImageField/settings_form', compact('instance', 'options'));
    }

    /**
     * {@inheritDoc}
     */
    public function instanceSettingsDefaults(Event $event, $instance, $options = [])
    {
        return [
            'extensions' => 'jpg,jpeg,png,bmp,gif,tif,tiff',
            'multi' => 1,
            'multi_custom' => 1,
            'upload_folder' => '',
            'description' => '',
            'preview' => '',
            'min_width' => '',
            'min_height' => '',
            'max_width' => '',
            'max_height' => '',
            'min_ratio' => '',
            'max_ratio' => '',
            'min_pixels' => '',
            'max_pixels' => '',
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function instanceViewModeForm(Event $event, $instance, $options = [])
    {
        $View = $event->subject();
        return $View->element('Field.ImageField/view_mode_form', compact('instance', 'options'));
    }

    /**
     * {@inheritDoc}
     */
    public function instanceViewModeDefaults(Event $event, $instance, $options = [])
    {
        switch ($options['viewMode']) {
            default:
                return [
                    'label_visibility' => 'above',
                    'hooktags' => true,
                    'hidden' => false,
                    'size' => 'thumbnail',
                    'link_type' => '',
                ];
        }
    }
}
