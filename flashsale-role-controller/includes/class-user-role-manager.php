<?php
class FRC_User_Role_Manager
{
    public static function activate()
    {
        add_role('mitra', 'Mitra', ['read' => true, 'level_0' => true]);
        add_role('user', 'User', ['read' => true, 'level_0' => true]);
    }
}
