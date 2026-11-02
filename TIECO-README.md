# Tieco Tech overlay (Batch 1 + 2, matched to Laravel 13 / Inertia 3 starter kit)

Extract over the project root (it replaces: routes/web.php, resources/views/app.blade.php, app/Models/User.php,
database/seeders/DatabaseSeeder.php). Everything else is new files.

## Steps (Laragon terminal, inside the project)
1. tar -xf %USERPROFILE%\Downloads\tieco-overlay.zip -C d:\laragon\www\tieco
2. Add to resources\css\app.css, right after the line  @import 'tw-animate-css';
       @import './tieco.css';
3. npm i @fontsource-variable/inter @fontsource/ibm-plex-sans-arabic
4. Edit .env (see .env.example.additions): APP_NAME, DB_* (MySQL), ADMIN_EMAIL, ADMIN_PASSWORD (12+ chars)
5. php artisan migrate:fresh --seed
6. php artisan test
7. php artisan serve   (one window)    npm run dev   (another window)    open http://localhost:8000

Not yet run by the author: expect small fixes on first run. Send the error text as is.
