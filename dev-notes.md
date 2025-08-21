## Create new Laravel project
composer create-project laravel/laravel your-project-name

## Run locally
php artisan serve
    --host=192.168.50.132 --port=8000

## Docker
docker login rg.fr-par.scw.cloud/namespace-giveortake-laravel-backend -u nologin --password-stdin <<< "$SCW_SECRET_KEY"

Build Docker image targeting amd64:
docker build --platform linux/amd64 -t giveortake-laravel-backend .

Re-tag and push:
docker tag giveortake-laravel-backend rg.fr-par.scw.cloud/namespace-giveortake-laravel-backend/giveortake-laravel-backend:15-Aug-2025__11.20.15 && docker push rg.fr-par.scw.cloud/namespace-giveortake-laravel-backend/giveortake-laravel-backend:15-Aug-2025__11.20.15

## Deployment
Make it executable: chmod +x deploy-scaleway.sh
Run it: ./deploy-scaleway.sh

## PHP ARTISAN
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
