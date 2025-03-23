# EVENT MANAGEMENT PROJECT  



## Introduction   
A project for Event Management .   


## Starting   
1. To start the project ⤵️   
```
composer create-project --prefer-dist laravel/laravel event-management
```
1. Now make the model ⤵️
```
php artisan make:model Event -m
```
```
php artisan make:model Attendee -m
```   
This makes a model with a migration
1. In .env file enable the database.   
1. Now let's migrate these models ⤵️   
```
php artisan migrate
```
1. Now let's make controller now


