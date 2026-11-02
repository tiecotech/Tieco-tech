# Batch 5: public pages

New: /{locale}/services, /services/{slug}, /about, /contact (saved to DB), /legal/{privacy|terms|requests-policy},
/robots.txt, /sitemap.xml. Header and footer now link to real pages.

Notes
- Service detail text (problems, deliverables, features, steps, faqs) comes from the services table; empty parts are hidden.
  Format per language: lists = ["text", ...]; steps = [{"title","text"}]; faqs = [{"q","a"}].
- Legal pages stay hidden (404) until published: `php artisan tieco:page privacy` (use --hide to unpublish).
  Seeded text is a draft, not legal advice. Have it reviewed first.
- The "Work" link still points at the home section until the portfolio page exists.
- Contact messages are stored in contact_messages; staff with messages.manage get a dashboard notification.
