<?php

namespace App\Controllers;

use App\Helpers\UserFormatter;
use App\Models\User;
use App\Repositories\UserRepository;

/**
 * User controller class.
 */
class UserController
{
    /**
     * @var UserRepository
     */
    private $userRepository;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->userRepository = new UserRepository();
    }

    /**
     * Get all users.
     *
     * @return string
     */
    public function index(): string
    {
        $users = $this->userRepository->findAll();
        $output = '';
        
        foreach ($users as $user) {
            $output .= UserFormatter::format($user) . PHP_EOL;
        }
        
        return $output;
    }

    /**
     * Get a user by ID.
     *
     * @param int $id
     * @return string
     */
    public function show(int $id): string
    {
        $user = $this->userRepository->findById($id);
        
        if (!$user) {
            return 'User not found.';
        }
        
        return UserFormatter::format($user);
    }

    /**
     * Create a new user.
     *
     * @param string $name
     * @param string $email
     * @return string
     */
    public function create(string $name, string $email): string
    {
        $user = $this->userRepository->create($name, $email);
        return 'User created: ' . UserFormatter::format($user);
    }

    /**
     * Get a user by ID as HTML.
     *
     * @param int $id
     * @return string
     */
    public function showHtml(int $id): string
    {
        $user = $this->userRepository->findById($id);
        
        if (!$user) {
            return '<div class="error">User not found.</div>';
        }
        
        return UserFormatter::formatHtml($user);
    }

    /**
     * Get a user by ID as JSON.
     *
     * @param int $id
     * @return string
     */
    public function showJson(int $id): string
    {
        $user = $this->userRepository->findById($id);
        
        if (!$user) {
            return json_encode(['error' => 'User not found.']);
        }
        
        return UserFormatter::formatJson($user);
    }
}
