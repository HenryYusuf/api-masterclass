<?php

namespace App\Permissions\V1;

use App\Models\User;

final class Abilities {
    public const CreateTicket = 'ticket:create';
    public const UpdateTicket = 'ticket:update';
    public const ReplaceTicket = 'ticket:replace';
    public const DeleteTicket = 'ticket:delete';

    public const CreateOwnTicket = 'ticket:own:create';
    public const UpdateOwnTicket = 'ticket:own:update';
    public const ReplaceOwnTicket = 'ticket:own:replace';
    public const DeleteOwnTicket = 'ticket:own:delete';

    public const CreateUser = 'user:create';
    public const UpdateUser = 'user:update';
    public const ReplaceUser = 'user:replace';
    public const DeleteUser = 'user:delete';

    public static function getAbilities(User $user)
    {
        // don't assign '*'
        if($user->is_manager) {
            return [
                // Ticket Abilities
                self::CreateTicket,
                self::UpdateTicket,
                self::ReplaceTicket,
                self::DeleteTicket,

                // User Abilities
                self::CreateUser,
                self::UpdateUser,
                self::ReplaceUser,
                self::DeleteUser,
            ];
        } else {
            return [
                self::CreateOwnTicket,
                self::UpdateOwnTicket,
                self::ReplaceOwnTicket,
                self::DeleteOwnTicket,
            ];
        }
    }
}