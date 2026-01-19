<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use TCG\Voyager\Models\DataRow;
use TCG\Voyager\Models\DataType;
use TCG\Voyager\Models\Menu;
use TCG\Voyager\Models\MenuItem;
use TCG\Voyager\Models\Permission;
use TCG\Voyager\Models\Role;

class OrganisationsBreadSeeder extends Seeder
{
    public function run()
    {
        // 1. Data Type
        $dataType = DataType::firstOrNew(['slug' => 'organisations']);
        if (!$dataType->exists) {
            $dataType->fill([
                'name'                  => 'organisations',
                'display_name_singular' => 'Organisation',
                'display_name_plural'   => 'Organisations',
                'icon'                  => 'voyager-company',
                'model_name'            => 'App\\Models\\Organisation',
                'policy_name'           => null,
                'controller'            => null,
                'generate_permissions'  => 1,
                'description'           => '',
            ])->save();
        }

        // 2. Data Rows
        $this->dataRow($dataType, 'id', 'number', 'ID', 1, 0, 0, 0, 0, 0, '{}', 1);
        $this->dataRow($dataType, 'nom', 'text', 'Nom', 1, 1, 1, 1, 1, 1, '{"validation":{"rule":"required|max:150"}}', 2);
        $this->dataRow($dataType, 'description', 'rich_text_box', 'Description', 0, 1, 1, 1, 1, 1, '{}', 3);
        $this->dataRow($dataType, 'email_contact', 'text', 'Email Contact', 0, 1, 1, 1, 1, 1, '{"validation":{"rule":"nullable|email|max:150"}}', 4);
        $this->dataRow($dataType, 'adresse', 'text', 'Adresse', 0, 1, 1, 1, 1, 1, '{}', 5);
        $this->dataRow($dataType, 'chef_organisation_id', 'relationship', 'Chef', 0, 1, 1, 1, 1, 1, '{"relationship":{"key":"id","label":"nom","page":"users","method":"chef","module":"Admin","class":"App\\\\Models\\\\User","order_by":"nom","order_dir":"asc","base_relationship":"chef","pivot_table":"users","pivot":"0","taggable":"0"}}', 6);
        $this->dataRow($dataType, 'image', 'image', 'Image', 0, 1, 1, 1, 1, 1, '{}', 7);
        $this->dataRow($dataType, 'created_at', 'timestamp', 'Created At', 0, 0, 1, 0, 0, 0, '{}', 8);
        $this->dataRow($dataType, 'updated_at', 'timestamp', 'Updated At', 0, 0, 0, 0, 0, 0, '{}', 9);

        // 3. Menu Item
        $menu = Menu::where('name', 'admin')->first();
        if ($menu) {
            $menuItem = MenuItem::firstOrNew([
                'menu_id' => $menu->id,
                'title'   => 'Organisations',
                'url'     => '',
                'route'   => 'voyager.organisations.index',
            ]);
            if (!$menuItem->exists) {
                $menuItem->fill([
                    'target'     => '_self',
                    'icon_class' => 'voyager-company',
                    'color'      => null,
                    'parent_id'  => null,
                    'order'      => 10,
                ])->save();
            }
        }

        // 4. Permissions
        Permission::generateFor('organisations');

        // Assign permissions to admin role
        $role = Role::where('name', 'admin')->first();
        if ($role) {
            $permissions = Permission::where('table_name', 'organisations')->get();
            $role->permissions()->syncWithoutDetaching($permissions->pluck('id')->toArray());
        }
    }

    protected function dataRow($type, $field, $type_row, $display_name, $required, $browse, $read, $edit, $add, $delete, $details, $order)
    {
        $dataRow = DataRow::firstOrNew([
            'data_type_id' => $type->id,
            'field'        => $field,
        ]);

        if (!$dataRow->exists) {
            $dataRow->fill([
                'type'         => $type_row,
                'display_name' => $display_name,
                'required'     => $required,
                'browse'       => $browse,
                'read'         => $read,
                'edit'         => $edit,
                'add'          => $add,
                'delete'       => $delete,
                'details'      => $details,
                'order'        => $order,
            ])->save();
        }
    }
}
