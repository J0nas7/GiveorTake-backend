composer create-project laravel/laravel your-project-name

php artisan serve --host=192.168.0.139 --port=8000


PHP ARTISAN:
    DATABASE STUFF:
        ## DROP all tables AND Recreate the tables by re-running all migrations
        migrate:fresh
        ## Optional: Seed the Database
        migrate:fresh --seed

        ## Runs a specific seeder class
        db:seed --class=GiveorTakeSeeder
        db:seed --class=DunderMifflinSeeder


        make:migration create_name_table
        make:seeder NameSeeder

    make:model Model
        --all ## Along with migrations, controllers, and factories.
        --controller
        --controller --resource
        --factory
        --migration
        --seed
        --table=GT_ModelName
        --fillable="Model_Field1,Model_Field2,Model_CreatedAt,Model_UpdatedAt,Model_DeletedAt"
        --guarded="Model_Field3"
        --timestamps=false

    make:controller OrganisationController --resource --api
