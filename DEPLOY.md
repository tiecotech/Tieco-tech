# النشر التلقائي: GitHub → الموقع (cPanel)

كل `git push` إلى فرع `main` يشغّل الملف `.github/workflows/deploy.yml`، وهو:

1. يشغّل الاختبارات، وإن فشل أي اختبار يتوقف ولا يرفع شيئاً.
2. يبني نسخة الإنتاج (`composer --no-dev` و`npm run build`).
3. يرفعها إلى الاستضافة عبر **SSH** (الطريقة الأفضل) أو عبر **FTP** إن لم يكن SSH متاحاً.
4. يشغّل `migrate` ويعيد بناء الكاش على السيرفر.

يمكنك متابعة كل عملية نشر من تبويب **Actions** في المستودع على GitHub.

---

## الإعداد لأول مرة (مرة واحدة فقط)

### 1) تجهيز المجلد على الاستضافة
- ضع المشروع **خارج** `public_html`، مثلاً في `/home/USER/tieco`.
- اجعل جذر الدومين (Document Root) يشير إلى `/home/USER/tieco/public`.
  من cPanel افتح **Domains** ثم عدّل الدومين وغيّر Document Root.
  إن كان الدومين الرئيسي لا يسمح بذلك فاحذف `public_html` واصنع رابطاً رمزياً (symlink) بدلاً منه:
  `ln -s /home/USER/tieco/public /home/USER/public_html`
- اختر **PHP 8.3** من **Select PHP Version**، وفعّل الإضافات `pdo_mysql` و`mbstring` و`intl` و`gd` و`zip` و`bcmath`.
- أنشئ قاعدة بيانات MySQL ومستخدماً لها من **MySQL Databases**.

### 2) ملف `.env` على السيرفر (لا يُرفع من GitHub أبداً)
أنشئ الملف `/home/USER/tieco/.env` يدوياً انطلاقاً من `.env.example`، واضبط فيه:
```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com
APP_KEY=            # ولّده بالأمر: php artisan key:generate
DB_DATABASE=... DB_USERNAME=... DB_PASSWORD=...
MAIL_MAILER=smtp ... (إعدادات البريد لتنبيهات الطلبات الجديدة)
DEPLOY_TOKEN=       # فقط إن كنت ستستخدم FTP: نص عشوائي طوله 40 حرفاً على الأقل
```

### 3) مجلد storage (مرة واحدة)
النشر لا يلمس مجلد `storage/` حتى لا تُحذف الملفات المرفوعة والسجلات. لذلك في أول مرة ارفع المجلد `storage/` كما هو، أو أنشئ هيكله بنفسك، ثم شغّل:
```
php artisan storage:link
php artisan migrate --force
php artisan db:seed --force     # أول مرة فقط: الحالات والأسعار وحساب المدير
```

### 4) أسرار GitHub
افتح المستودع على GitHub، ثم **Settings** ثم **Secrets and variables** ثم **Actions** ثم **New repository secret**.

**إن كانت الاستضافة تدعم SSH (يُنصح به):** تجده في cPanel تحت **SSH Access**.
| السر | القيمة |
|---|---|
| `SSH_HOST` | عنوان السيرفر (مثل `server123.host.com` أو IP) |
| `SSH_PORT` | المنفذ، وغالباً `22` (بعض الاستضافات تستخدم `65002` أو غيره) |
| `SSH_USER` | اسم مستخدم cPanel |
| `SSH_KEY` | **المفتاح الخاص** (private key) كاملاً |
| `DEPLOY_PATH` | `/home/USER/tieco` |
| `PHP_BIN` | (اختياري) مسار PHP 8.3 إن كان الأمر `php` يشير لنسخة أقدم، مثل `/opt/cpanel/ea-php83/root/usr/bin/php` |

طريقة إنشاء المفتاح: من cPanel افتح **SSH Access** ثم **Manage SSH Keys** ثم **Generate a New Key**، ثم **Authorize** للمفتاح العام، ثم انسخ المفتاح الخاص (Private) وضعه في `SSH_KEY`.

**إن لم يكن هناك SSH، استخدم FTP:**
| السر | القيمة |
|---|---|
| `FTP_SERVER` | مثل `ftp.your-domain.com` |
| `FTP_USERNAME` / `FTP_PASSWORD` | حساب FTP من **FTP Accounts** |
| `FTP_DIR` | مجلد المشروع كما يراه حساب FTP، مع `/` في آخره، مثل `/tieco/` |
| `SITE_URL` | `https://your-domain.com` |
| `DEPLOY_TOKEN` | نفس القيمة الموجودة في `.env` على السيرفر |

ملاحظة: الرفع عبر FTP أبطأ، خصوصاً أول مرة لأنه يرفع مجلد `vendor` كاملاً. بعد ذلك يرفع الملفات المتغيرة فقط.

### 5) Cron (مستحسن)
من cPanel افتح **Cron Jobs** وأضف مهمة تعمل كل دقيقة:
```
* * * * * cd /home/USER/tieco && php artisan schedule:run >> /dev/null 2>&1
```

---

## الاستخدام اليومي
```
git add .
git commit -m "وصف التعديل"
git push
```
بعد دقائق قليلة يكون التعديل على الموقع. وإن فشل شيء تصلك رسالة من GitHub، وتجد التفاصيل في تبويب **Actions**.
