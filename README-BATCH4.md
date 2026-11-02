# Tieco Tech: Batch 4a (admin area: dashboard, requests, prices and discounts)

Not yet run by the author (no PHP in the authoring environment). Run the tests first and send any failure text.

## Install (Laragon terminal, inside the project)
1. tar -xf %USERPROFILE%\Downloads\tieco-batch4.zip -C d:\laragon\www\tieco
2. php artisan test
3. npm run build   (or keep npm run dev running)
4. Open http://localhost:8000/login  (ADMIN_EMAIL / ADMIN_PASSWORD from .env), you land on /admin

## What it does
- /admin: live numbers from the database (requests by status, last 14 days, latest requests, recent activity).
- /admin/requests: search (number, name, phone, email), filter (status, type, dates), CSV export (managers and admins).
- /admin/requests/{id}: customer and project details, frozen price lines, private attachment download, status changes with history,
  assignment, internal notes and contact log.
- /admin/pricing (admins only): edit base prices, page limits, add-on prices, discounts (create, activate, conditions, caps).
  Saved changes reach the calculator immediately; old requests keep their frozen snapshots. Every change is logged with old and new values.
- Public registration is closed (/register returns 404). Staff accounts are created by an administrator.
- Roles: admin (everything), manager (requests, quotes, export), editor (content), viewer (read-only requests).
- Admin language follows the user's profile and can be switched in the top bar (English / Arabic with RTL).

## Next (4b / 4c)
Quotes (versions), site content and team management, user and role management, activity log screen, contact messages.
