# lbudget

Copy `.env.example` to `.env`. Using laravel/Homestead [https://github.com/laravel/Homestead] is a good idea to start.

Uses google login. Please get yourself a client id and secret from google. Doesn't currently support an alternative login method.
https://developers.google.com/identity/sign-in/web/devconsole-project

use `composer install -o` and `npm run dev` to build dependencies and assets.
(I run composer inside vagrant and npm in my windows host. Seems to work better, but you may have better luck if you're not on a windows host.)

Use `artisan migrate:refresh` to get your database schema set up

If you have a db from zbudget, you can import by using `artisan import:zbudget` command.

If you instead would like to seed with random data, use `artisan db:seed`
