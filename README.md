# lbudget

Copy `.env.example` to `.env`. Provide the database information in `.env`. You can provide a database with a blank schema. Using laravel/Homestead [https://github.com/laravel/Homestead] is a good idea to start, for local development, but isn't required. The app should work with most SQL db servers, but postgres is the one I've used so far.

Get yourself a client id and secret from google. lbudget doesn't currently support an alternative login method. https://developers.google.com/identity/sign-in/web/devconsole-project make sure to add those to `.env`.

use `composer install -o` and `npm run dev` to build dependencies and assets.
(I run composer inside vagrant and npm in my windows host. Seems to work better, but you may have better luck if you're not on a windows host.)

Use `artisan migrate:refresh` to get your database schema set up (will also clear any data).

If you have a db from zbudget, you can import by using `artisan import:zbudget` command.

If you instead would like to seed with random data, use `artisan db:seed`
