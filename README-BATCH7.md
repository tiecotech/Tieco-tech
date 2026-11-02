# Batch 7
- auth/login.tsx: branded staff sign-in (AR/EN toggle), css/login.css.
- Free guided advisor (no AI service): components/guide/guide-widget.tsx + css/guide.css. "Let's talk" opens it; floating button on all pages.
  Result links to /start?type=..&options=.. (builder pre-fills) or WhatsApp with a ready message.
- Builder reads presetPages/presetOptions (sanitised in BuilderController).
- Email optional: contact form puts phone first; builder email marked optional (either phone or email is still required).
- lang/{en,ar}/site.php replaced (batch5 strings + guide strings).
