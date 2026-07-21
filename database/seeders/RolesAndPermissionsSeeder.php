<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Toutes les permissions de la plateforme, regroupées par module.
     * NOTE : ces noms doivent rester synchronisés avec les middlewares
     * `permission:` déclarés dans routes/web.php et les directives
     * @can(...) utilisées dans les vues Blade.
     */
    protected array $permissions = [
        // Dashboard
        'view dashboard',
        'view analytics',

        // Administration
        'view users', 'create users', 'edit users', 'delete users',
        'manage roles', 'manage permissions', 'manage settings',

        // Knowledge Base
        'view articles', 'view draft articles',
        'create articles', 'edit articles', 'edit all articles',
        'delete articles', 'publish articles',
        'manage categories', 'moderate comments', 'export documentation',

        // Sondages
        'view polls', 'create polls', 'edit polls', 'delete polls',
        'vote polls', 'export poll results',

        // Réunions
        'view meetings', 'create meetings', 'edit meetings',
        'delete meetings', 'manage meetings', 'join meetings', 'record meetings',

        // Chat
        'send messages', 'view messages', 'delete messages',
        'moderate messages', 'pin messages',
        'create groups', 'manage groups',
    ];

    /**
     * Socle commun à TOUS les rôles : ce sont les briques de base de la
     * collaboration, accessibles indépendamment du niveau de droits sur
     * la documentation ou l'administration.
     */
    protected array $basePermissions = [
        'view dashboard',
        'view messages', 'send messages', 'create groups',
        'view polls', 'create polls', 'vote polls',
        'view meetings', 'create meetings', 'join meetings',
        'view articles',
    ];

    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Création idempotente : ne plante pas si le seeder est rejoué
        foreach ($this->permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $roles = [
            // Tout, sans exception
            'super-admin' => $this->permissions,

            // Tout sauf la gestion fine des rôles/permissions
            'admin' => array_values(array_diff($this->permissions, [
                'manage roles', 'manage permissions',
            ])),

            // Gère la documentation + usage complet de la collaboration
            'editor' => array_merge($this->basePermissions, [
                'view draft articles', 'create articles', 'edit articles',
                'edit all articles', 'publish articles', 'manage categories',
                'moderate comments', 'export documentation',
                'edit polls', 'delete polls', 'export poll results',
                'edit meetings', 'manage meetings', 'record meetings',
                'pin messages', 'moderate messages',
            ]),

            // Crée des articles soumis à validation, usage standard sinon
            'author' => array_merge($this->basePermissions, [
                'create articles', 'edit articles',
            ]),

            // Lecture seule sur la documentation, usage standard sinon
            'viewer' => $this->basePermissions,
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->syncPermissions(array_unique($rolePermissions));
        }

        // === Compte Super Admin par défaut, pratique pour les tests locaux ===
        $user = User::firstOrCreate(
            ['email' => 'admin@nexus.com'],
            ['name' => 'Super Admin', 'password' => bcrypt('password123')]
        );

        if (! $user->hasRole('super-admin')) {
            $user->assignRole('super-admin');
        }

        // Si des comptes existaient déjà avant ce seeder, on leur donne au
        // minimum le rôle "viewer" pour que la plateforme reste utilisable
        // (évite les 403 partout après un premier `php artisan migrate:fresh --seed`).
        User::doesntHave('roles')->get()->each(fn (User $u) => $u->assignRole('viewer'));

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command->info('✔ Rôles & permissions créés.');
        $this->command->info('✔ Super Admin : admin@nexus.com / password123');
    }
}
