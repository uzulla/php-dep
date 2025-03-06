<?php

namespace App\Helpers;

use App\Models\User;

/**
 * User formatter helper class.
 */
class UserFormatter
{
    /**
     * Format a user as a string.
     *
     * @param User $user
     * @return string
     */
    public static function format(User $user): string
    {
        return sprintf(
            'User #%d: %s <%s>',
            $user->getId(),
            $user->getName(),
            $user->getEmail()
        );
    }

    /**
     * Format a user as HTML.
     *
     * @param User $user
     * @return string
     */
    public static function formatHtml(User $user): string
    {
        return sprintf(
            '<div class="user" data-id="%d">
                <h3>%s</h3>
                <p>Email: <a href="mailto:%s">%s</a></p>
            </div>',
            $user->getId(),
            htmlspecialchars($user->getName()),
            htmlspecialchars($user->getEmail()),
            htmlspecialchars($user->getEmail())
        );
    }

    /**
     * Format a user as JSON.
     *
     * @param User $user
     * @return string
     */
    public static function formatJson(User $user): string
    {
        return json_encode([
            'id' => $user->getId(),
            'name' => $user->getName(),
            'email' => $user->getEmail(),
        ]);
    }
}
