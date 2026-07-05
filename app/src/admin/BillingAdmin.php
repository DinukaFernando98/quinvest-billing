<?php

namespace App\Admin;

use SilverStripe\Admin\ModelAdmin;
use App\Model\BillingFormSubmission;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\Forms\GridField\GridFieldDataColumns;
use SilverStripe\Forms\GridField\GridFieldExportButton;
use SilverStripe\Forms\GridField\GridFieldPrintButton;

class BillingAdmin extends ModelAdmin
{
    private static $managed_models = [
        BillingFormSubmission::class,
    ];

    private static $url_segment = 'billing-forms';
    private static $menu_title = 'Billing Forms';
    private static $menu_icon_class = 'font-icon-book-open';
    private static $menu_priority = 85;

    public function getEditForm($id = null, $fields = null)
    {
        $form = parent::getEditForm($id, $fields);

        // Customize the grid field for BillingFormSubmission
        if ($this->modelClass === BillingFormSubmission::class) {
            /** @var GridField $gridField */
            $gridField = $form->Fields()->dataFieldByName($this->sanitiseClassName($this->modelClass));

            if ($gridField) {
                $config = GridFieldConfig_RecordEditor::create();

                // Get the default config and customize it
                $dataColumns = $config->getComponentByType(GridFieldDataColumns::class);

                if ($dataColumns) {
                    $dataColumns->setDisplayFields([
                        'ID' => 'ID',
                        'SerialNumber' => 'Serial Number',
                        'FormSubmission.SalespersonName' => 'Salesperson',
                        'FormSubmission.RESNumber' => 'RES Number',
                        'UploadedBy.Email' => 'Uploaded By',
                        'BillingFile.Name' => 'Billing File',
                        'Created.Nice' => 'Submitted Date',
                        'getFileSize' => 'File Size',
                        'getDownloadLink' => 'Download',
                    ]);

                    $dataColumns->setFieldFormatting([
                        'getDownloadLink' => function($value, $item) {
                            if ($item->BillingFile()->exists()) {
                                return sprintf(
                                    '<a href="%s" target="_blank" class="btn btn-primary btn-sm" download>Download</a>',
                                    $item->BillingFile()->getURL()
                                );
                            }
                            return '<span class="text-muted">No file</span>';
                        },
                        'getFileSize' => function($value, $item) {
                            if ($item->BillingFile()->exists()) {
                                return $item->getFileSize();
                            }
                            return '-';
                        }
                    ]);
                }

                // Add export and print buttons
                $config->addComponents([
                    new GridFieldExportButton('buttons-before-left'),
                    new GridFieldPrintButton('buttons-before-left'),
                ]);

                $gridField->setConfig($config);
            }
        }

        return $form;
    }

    public function getList()
    {
        $list = parent::getList();

        // Add default sorting by creation date (newest first)
        if ($this->modelClass === BillingFormSubmission::class) {
            $list = $list->sort('Created', 'DESC');
        }

        return $list;
    }
}