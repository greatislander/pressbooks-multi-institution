<?php

namespace Tests\Traits;

use PressbooksMultiInstitution\Models\Institution;
use PressbooksMultiInstitution\Models\InstitutionUser;
use Pressbooks\DataCollector\Book as DataCollector;

trait CreatesModels
{
    protected function newUser(array $properties = []): int
    {
        global $wpdb;

        $wpdb->query('START TRANSACTION');

        $properties = [
            'first_name' => $properties['first_name'] ?? 'John',
            'last_name' => $properties['last_name'] ?? 'Doe',
            'user_login' => $properties['user_login'] ?? 'johndoe',
            'user_email' => $properties['user_email'] ?? 'johndoe@fakedomain.edu',
        ];

        $wpdb->delete($wpdb->users, [
            'user_login' => $properties['user_login'],
        ]);

        $user = $this->factory()->user->create($properties);

        $wpdb->query('COMMIT');

        return $user;
    }

    protected function newSuperAdmin(array $properties = []): int
    {
        return tap($this->newUser($properties), function (int $id) {
            grant_super_admin($id);
        });
    }

    protected function newNetworkManager(array $properties = []): int
    {
        return tap($this->newSuperAdmin($properties), function (int $id) {
            update_site_option('pressbooks_network_managers', [$id]);
        });
    }

    protected function newInstitutionalManager(Institution $institution, array $properties = []): int
    {
        return tap($this->newNetworkManager($properties), function (int $id) use ($institution) {
            $institution->users()->create([
                'user_id' => $id,
                'manager' => true,
            ]);
        });
    }

    protected function newBook(array $properties = []): int
    {
        global $wpdb;

        $wpdb->query('START TRANSACTION');

        add_filter('pb_redirect_to_new_book', '__return_false');

        $properties = [
            'path' => $properties['path'] ?? 'fakepath',
            'title' => $properties['title'] ?? 'Fake Book',
        ];

        $wpdb->delete($wpdb->blogs, [
            'path' => "/{$properties['path']}/",
        ]);

        $blog = $this->factory()->blog->create($properties);

        DataCollector::init()->copyBookMetaIntoSiteTable($blog);

        $wpdb->query('COMMIT');

        switch_to_blog($blog);

        return $blog;
    }

    public function createInstitution(array $properties = []): Institution
    {
        return Institution::create([
            'name' => $properties['name'] ?? 'Fake Institution',
            'book_limit' => $properties['book_limit'] ?? 10,
            'user_limit' => $properties['user_limit'] ?? 10,
        ]);
    }

    public function createInstitutionsUsers(int $institutionsLimit, int $usersLimit): void
    {
        $institutions = [];
        for ($i = 0; $i < $institutionsLimit; $i++) {
            $institutions[] = $this->createInstitution(['name' => "Institution {$i}"]);
        }

        for ($i = 0; $i < $usersLimit; $i++) {
            $user_id = $this->newUser([
                'user_login' => "johndoe{$i}",
                'user_email' => "j{$i}@fake.test",
                'first_name' => "John{$i}",
                'last_name' => "Doe{$i}",
            ]);

            InstitutionUser::query()->create([
                'user_id' => $user_id,
                'institution_id' => $institutions[array_rand($institutions)]->id,
            ]);
        }
    }

    /**
     * This method runs a callback without triggering a given filter.
     * It returns the expected value from the callback.
     *
     * @param  string  $hook The hook name to be skipped
     * @param  callable  $callback The method that should run
     * @return mixed The value from the callback method
     */
    protected function runWithoutFilter(string $hook, callable $callback): mixed
    {
        global $wp_filter;

        $handler = $wp_filter[$hook] ?? null;

        if ($handler instanceof WP_Hook) {
            unset($wp_filter[$hook]);
        }

        $response = call_user_func($callback);

        if ($handler instanceof WP_Hook) {
            $wp_filter[$hook] = $handler;
        }

        return $response;
    }
}
