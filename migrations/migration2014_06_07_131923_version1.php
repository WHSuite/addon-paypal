<?php
namespace Addon\Paypal\Migrations;

use \App\Libraries\BaseMigration;

class Migration2014_06_07_131923_version1 extends BaseMigration
{
    /**
     * migration 'up' function to install items
     *
     * @param   int     addon_id
     */
    public function up($addon_id)
    {
        $Gateway = new \Gateway();
        $Gateway->slug = 'paypal';
        $Gateway->name = 'Paypal';
        $Gateway->addon_id = $addon_id;
        $Gateway->is_merchant = 0;
        $Gateway->process_cc = 0;
        $Gateway->process_ach = 0;
        $Gateway->is_active = 1;
        $Gateway->sort = 10;
        $Gateway->save();

        // Create the settings category
        $category = new \SettingCategory();
        $category->slug = 'paypal';
        $category->title = 'paypal_settings';
        $category->is_visible = '1';
        $category->sort = '99';
        $category->addon_id = $addon_id;
        $category->save();

        \Setting::insert(
            array(
                array(
                    'slug' => 'client_id',
                    'title' => 'paypal_client_id',
                    'description' => '',
                    'field_type' => 'text',
                    'setting_category_id' => $category->id,
                    'editable' => 1,
                    'required' => 1,
                    'addon_id' => $addon_id,
                    'sort' => 10,
                    'value' => '',
                    'default_value' => '',
                    'created_at' => $this->date,
                    'updated_at' => $this->date
                ),
                array(
                    'slug' => 'secret_code',
                    'title' => 'paypal_secret_code',
                    'description' => '',
                    'field_type' => 'text',
                    'setting_category_id' => $category->id,
                    'editable' => 1,
                    'required' => 1,
                    'addon_id' => $addon_id,
                    'sort' => 20,
                    'value' => '',
                    'default_value' => '',
                    'created_at' => $this->date,
                    'updated_at' => $this->date
                ),
                array(
                    'slug' => 'testmode',
                    'title' => 'paypal_testmode',
                    'description' => '',
                    'field_type' => 'checkbox',
                    'setting_category_id' => $category->id,
                    'editable' => 1,
                    'required' => 1,
                    'addon_id' => $addon_id,
                    'sort' => 30,
                    'value' => 1,
                    'default_value' => 1,
                    'created_at' => $this->date,
                    'updated_at' => $this->date
                ),
                array(
                    'slug' => 'paypal_experience_id',
                    'title' => 'paypal_webprofile',
                    'description' => 'paypal_webprofile_desc',
                    'field_type' => 'text',
                    'setting_category_id' => $category->id,
                    'editable' => 1,
                    'required' => 0,
                    'addon_id' => $addon_id,
                    'sort' => 40,
                    'value' => '',
                    'default_value' => 1,
                    'created_at' => $this->date,
                    'updated_at' => $this->date
                )
            )
        );
    }

    /**
     * migration 'down' function to delete items
     *
     * @param   int     addon_id
     */
    public function down($addon_id)
    {
        // Remove gateway record
        $Gateway = \Gateway::where('addon_id', '=', $addon_id)
            ->where('slug', '=', 'paypal')
            ->first();

        \GatewayCurrency::where('gateway_id', '=', $Gateway->id)
            ->delete();

        $Gateway->delete();

        // Remove all settings
        \Setting::where('addon_id', '=', $addon_id)->delete();

        // Remove all settings groups
        \SettingCategory::where('addon_id', '=', $addon_id)->delete();
    }
}
