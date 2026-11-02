# Tieco Tech: Batch 3 (Project Builder + live price calculator)

Not yet run by the author (no PHP in the authoring environment). Run the tests first and send any failure text.

## Install (Laragon terminal, inside the project)
1. tar -xf %USERPROFILE%\Downloads\tieco-batch3.zip -C d:\laragon\www\tieco
2. php artisan db:seed --class=CompanyContactSeeder     (phone +963992355844, currency USD)
3. php artisan test
4. npm run build      (or keep npm run dev running)
5. Open http://localhost:8000/en/start  and  /ar/start

## Where the calculator is
- Engine: app/Services/Pricing/PricingCalculator.php  (single source of truth, integer money)
- Live estimate endpoint: POST /{locale}/start/estimate  (the page asks the server on every change)
- Final save: POST /{locale}/start  re-prices on the server, ignores any browser-sent price,
  and stores the price snapshot with the request.
- Screen: resources/js/pages/builder.tsx and resources/js/components/builder/*

## Behaviour
- 4 steps: project, customize (pages slider+field+buttons+quick picks, add-ons, platform, creative), details, review.
- Mobile apps require a platform. Add-ons are limited to those compatible with the project type.
- Discounts are seeded OFF; turn them on (is_active) in discount_rules until the admin panel (Batch 4) exists.
- Attachments: private disk (storage/app/private or local), random names, type and size checked on the server.
- Staff (admin, manager) receive a dashboard notification; the customer gets an email only if a real mail provider is configured.
- Honeypot field + rate limits (estimate 90/min, submit 8/min).
