<?php

namespace App\Repositories;

use App\Models\User;

/**
 * User repository class.
 */
class UserRepository
{
    /**
     * @var array
     */
    private $users = [];

    /**
     * Constructor.
     */
    public function __construct()
    {
        // Simulate database data
        $this->users = [
            1 => new User(1, 'John Doe', 'john@example.com'),
            2 => new User(2, 'Jane Smith', 'jane@example.com'),
            3 => new User(3, 'Bob Johnson', 'bob@example.com'),
        ];
    }

    /**
     * Find a user by ID.
     *
     * @param int $id
     * @return User|null
     */
    public function findById(int $id): ?User
    {
        return $this->users[$id] ?? null;
    }

    /**
     * Get all users.
     *
     * @return User[]
     */
    public function findAll(): array
    {
        return $this->users;
    }

    /**
     * Create a new user.
     *
     * @param string $name
     * @param string $email
     * @return User
     */
    public function create(string $name, string $email): User
    {
        $id = max(array_keys($this->users)) + 1;
        $user = new User($id, $name, $email);
        $this->users[$id] = $user;
        
        return $user;
    }
}
