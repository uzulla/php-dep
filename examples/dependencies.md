# File: /Users/zishida/php-dep/examples/src/Controllers/UserController.php

```php
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

```

## Dependencies

### File: ./src//Helpers/UserFormatter.php

```php
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

```

### File: ./src//Models/User.php

```php
<?php

namespace App\Models;

/**
 * User model class.
 */
class User
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $email;

    /**
     * @param int $id
     * @param string $name
     * @param string $email
     */
    public function __construct(int $id, string $name, string $email)
    {
        $this->id = $id;
        $this->name = $name;
        $this->email = $email;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }
}

```

### File: ./src//Repositories/UserRepository.php

```php
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

```

