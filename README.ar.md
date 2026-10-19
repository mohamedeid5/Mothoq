# Mothoq | موثوق

[English](README.md) | **العربية**

منصة لاكتشاف مراكز صيانة السيارات في مصر، ومقارنة خدماتها وتقييماتها وحجز المواعيد. يوفّر المشروع واجهة ويب عربية وREST API بإصدار `v1`، مع صلاحيات منفصلة للعملاء وأصحاب المراكز والإدارة.

## المحتويات

- [المميزات](#المميزات)
- [التقنيات](#التقنيات)
- [التشغيل باستخدام Docker](#التشغيل-باستخدام-docker)
- [التشغيل بدون Docker](#التشغيل-بدون-docker)
- [متغيرات البيئة](#متغيرات-البيئة)
- [دليل API مختصر](#دليل-api-مختصر)
- [قواعد الحجوزات](#قواعد-الحجوزات)
- [هيكل المشروع](#هيكل-المشروع)
- [الاختبارات](#الاختبارات)
- [التشغيل والنشر](#التشغيل-والنشر)
- [حل المشكلات](#حل-المشكلات)

## المميزات

- تصفّح المراكز وتصفيتها حسب المحافظة والمدينة والخدمة وماركة السيارة وحالة التوثيق.
- عرض تفاصيل المركز والخدمات والصور ومواعيد العمل والتقييمات.
- إنشاء الحجوزات وإلغاؤها وإعادة جدولتها، مع ضبط مدة الخدمة والسعة المتاحة.
- إدارة ساعات العمل الأسبوعية واستثناءات الأيام والإجازات.
- إدارة المالك لمراكزه وصورها وحجوزاتها، وإدارة المشرف لحالة المراكز وتوثيقها ومراجعة التقييمات.
- تسجيل الحسابات والدخول واستعادة كلمة المرور عبر الويب، ومصادقة API باستخدام Sanctum.
- رسائل بريد مرتبطة بالحجوزات، ومعالجة الطوابير باستخدام Horizon وRedis.

## التقنيات

| المكوّن | المستخدم في المشروع |
| --- | --- |
| Backend | PHP 8.4، Laravel 13 |
| Authentication | Laravel Sanctum 4 |
| Frontend | Blade، Tailwind CSS 4، Vite 8 |
| Database | MySQL 8.4 في Docker؛ SQLite في الاختبارات الافتراضية |
| Cache / Sessions / Queues | Redis، Laravel Horizon 5 |
| Tests | PHPUnit 12 |
| Tooling | Composer 2، Node.js 24، Docker Compose |
| Storage / Mail | تخزين محلي أو S3، وبريد log محليًا مع دعم SES |

الإصدارات الدقيقة لاعتماديات PHP محفوظة في [composer.lock](composer.lock)، واعتماديات الواجهة في [package-lock.json](package-lock.json).

## التشغيل باستخدام Docker

المتطلبات: Git وDocker مع Compose. نفّذ الأوامر من مجلد المشروع بعد استنساخ المستودع.

### 1. إعداد البيئة

```bash
cp .env.example .env
```

استخدم هذه الخطوة في نسخة جديدة فقط. الإعدادات الافتراضية تستخدم `mysql` و`redis` كأسماء خدمات داخل Docker. يمكن تعديل المنافذ من `.env` إذا كانت مستخدمة بالفعل.

### 2. بناء الخدمات وتشغيل التطبيق

```bash
docker compose -f compose.yaml -f compose.dev.yaml up -d --build mysql redis app nginx node
```

صورة التطوير تثبّت اعتماديات Composer، وملف الدخول يولّد مفتاح التطبيق عند غيابه وينشئ رابط التخزين. خدمة `node` تثبّت اعتماديات الواجهة وتشغّل Vite.

### 3. تجهيز قاعدة البيانات وتشغيل الطوابير

```bash
docker compose -f compose.yaml -f compose.dev.yaml exec app php artisan migrate --no-interaction
docker compose -f compose.yaml -f compose.dev.yaml up -d horizon
```

منفذ الويب الافتراضي `8080` على الجهاز المحلي، ومنفذ Vite هو `5173`. اضبط `APP_URL` ليتطابق مع عنوان الوصول الذي تستخدمه.

### 4. بيانات تجريبية اختيارية

على قاعدة بيانات تطوير فقط:

```bash
docker compose -f compose.yaml -f compose.dev.yaml exec app php artisan db:seed --no-interaction
```

يشمل الـ seeder بيانات المحافظات والمدن والخدمات والسيارات والمراكز والتقييمات والحجوزات. وينشئ حسابي تجربة:

| الدور | البريد | كلمة المرور |
| --- | --- | --- |
| الإدارة | `admin@mothoq.test` | `password` |
| مالك مركز | `owner@mothoq.test` | `password` |

هذه حسابات تطوير؛ لا تستخدم البيانات التجريبية في الإنتاج. يمكن إنشاء حساب عميل من صفحة التسجيل.

لإيقاف الخدمات مع الاحتفاظ ببياناتها:

```bash
docker compose -f compose.yaml -f compose.dev.yaml down
```

## التشغيل بدون Docker

استخدم PHP 8.4 وComposer 2 وNode.js 24 وMySQL وRedis. راجع [Dockerfile التطوير](docker/php/Dockerfile) لقائمة امتدادات PHP المستخدمة، ومنها `pdo_mysql` و`pdo_sqlite` و`redis` و`pcntl`.

```bash
composer install
cp .env.example .env
php artisan key:generate --no-interaction
npm ci
```

قبل الترحيل، أنشئ قاعدة البيانات والمستخدم وعدّل `.env`:

- `DB_HOST=127.0.0.1` و`REDIS_HOST=127.0.0.1` للخدمات المحلية.
- اضبط `DB_PORT` و`DB_DATABASE` و`DB_USERNAME` و`DB_PASSWORD` حسب إعدادك.
- إذا كنت تستخدم MySQL وRedis من Docker مع PHP على جهازك، فالمنافذ الافتراضية المنشورة هي `33061` و`63790` على الترتيب.

ثم نفّذ:

```bash
php artisan migrate --no-interaction
php artisan storage:link --no-interaction
npm run build
php artisan serve --port=8080 --no-interaction
```

شغّل كل أمر مما يلي في طرفية منفصلة أثناء التطوير:

```bash
npm run dev
php artisan horizon --no-interaction
php artisan schedule:work --no-interaction
```

## متغيرات البيئة

القالب الكامل موجود في [.env.example](.env.example).

| المتغير | الغرض |
| --- | --- |
| `APP_URL` | عنوان التطبيق المستخدم لتوليد الروابط |
| `APP_LOCALE` / `APP_FALLBACK_LOCALE` | العربية افتراضيًا، والإنجليزية لغة احتياطية |
| `DB_*` | اتصال قاعدة البيانات |
| `REDIS_*` | اتصال Redis؛ العميل الافتراضي `phpredis` |
| `SESSION_DRIVER` / `CACHE_STORE` / `QUEUE_CONNECTION` | تستخدم `redis` افتراضيًا |
| `SANCTUM_EXPIRATION` | مدة صلاحية توكن API بالدقائق؛ الافتراضي `43200` |
| `CENTER_IMAGES_DISK` | قرص صور المراكز؛ `public` محليًا أو `s3` |
| `AWS_*` | إعدادات التخزين والخدمات المرتبطة بـ AWS |
| `MAIL_MAILER` | `log` محليًا: تُكتب الرسائل في السجلات بدل إرسالها |
| `MYSQL_ATTR_SSL_CA` | مسار شهادة CA لاتصالات MySQL المشفّرة في الإنتاج |
| `APP_PORT` / `VITE_PORT` | منافذ خدمات Docker على الجهاز |

ملف `.env` غير متتبّع في Git. احتفظ بالقيم السرية خارج المستودع.

## دليل API مختصر

كل المسارات التالية تبدأ بـ `/api/v1`. أرسل `Accept: application/json`، وللطلبات المحمية أرسل `Authorization: Bearer <token>`.

### المصادقة

| الطريقة | المسار | الوظيفة |
| --- | --- | --- |
| POST | `/auth/register` | تسجيل حساب |
| POST | `/auth/login` | إصدار توكن |
| GET | `/auth/me` | المستخدم الحالي |
| DELETE | `/auth/logout` | إلغاء التوكن الحالي |

جسم طلب الدخول:

```json
{
  "email": "customer@example.com",
  "password": "your-password",
  "device_name": "local-client"
}
```

استخدم بيانات حساب موجود. تعيد الاستجابة المستخدم في `data.user` والتوكن في `data.token`.

### المسارات العامة

| الطريقة | المسار | الوظيفة |
| --- | --- | --- |
| GET | `/governorates` | المحافظات |
| GET | `/governorates/{governorate}/cities` | المدن التابعة للمحافظة |
| GET | `/services` | الخدمات |
| GET | `/car-brands` | ماركات السيارات |
| GET | `/service-centers` | قائمة المراكز |
| GET | `/service-centers/{slug}` | تفاصيل مركز |
| GET | `/service-centers/{slug}/schedule?date=YYYY-MM-DD` | جدول يوم محدد |

تدعم قائمة المراكز `governorate` و`city` و`service` و`car_brand` بقيم slug، و`verified` و`page` و`per_page` بحد أقصى 50 نتيجة في الصفحة. اختيار مدينة يتطلب المحافظة التابعة لها.

### حجوزات العملاء

| الطريقة | المسار | الوظيفة |
| --- | --- | --- |
| GET | `/bookings` | حجوزات العميل الحالي |
| POST | `/service-centers/{slug}/bookings` | إنشاء حجز |
| DELETE | `/bookings/{id}` | إلغاء حجز |
| PATCH | `/bookings/{id}/reschedule` | إعادة جدولة حجز |

إنشاء حجز يحتاج دور `customer` وترويسة `Idempotency-Key` تحتوي UUID v4. استخدم مفتاحًا جديدًا لكل طلب منطقي جديد، وأعد استخدام نفس المفتاح والبيانات عند إعادة محاولة الطلب نفسه.

مثال جسم الطلب، بعد استبدال الخدمة والموعد بقيم متاحة فعلًا:

```json
{
  "service_id": 1,
  "customer_phone": "01012345678",
  "scheduled_at": "2027-01-15T10:00",
  "notes": "فحص دوري للسيارة"
}
```

الموعد يجب أن يكون في المستقبل بتوقيت مصر المحلي، بدون `Z` أو إزاحة مثل `+02:00`. تُقرأ مدة الخدمة من إعداد المركز، ولا يرسلها العميل.

### التقييمات والإدارة

- إنشاء تقييم: `POST /service-centers/{slug}/reviews`.
- تعديل أو حذف تقييم: `PATCH /reviews/{id}` و`DELETE /reviews/{id}`، حسب الصلاحيات.
- مسارات المالك تحت `/owner`، وتتطلب دور `center_owner` وتقتصر على موارده المسموح بها.
- مسارات الإدارة تحت `/admin`، وتتطلب دور `admin`.
- تشمل إدارة المراكز: الصور، ساعات العمل، استثناءات الجدول، مدة الخدمات وسعة الحجوزات. تشمل الإدارة أيضًا مراجعة التقييمات وحالة المراكز وتوثيقها.

هذا ملخص للمسارات، وليس مرجعًا كاملًا لكل حقول الطلبات. راجع [تعريف المسارات](routes/api.php) و[قواعد التحقق](app/Http/Requests)، أو اعرض القائمة الفعلية:

```bash
php artisan route:list --path=api --except-vendor --no-interaction
```

## قواعد الحجوزات

- تُحفظ المواعيد وتُقارن بتوقيت مصر المحلي `Africa/Cairo`.
- استثناء التاريخ له أولوية على الجدول الأسبوعي؛ عدم إعداد جدول يعني عدم الإتاحة.
- الحجوزات `pending` و`accepted` تستهلك السعة المتاحة.
- يجب ضبط سعة المركز ومدة الخدمة قبل استقبال الحجوزات؛ القيم غير المحددة لا تعني سعة غير محدودة أو مدة افتراضية.
- يُحفظ وقت نهاية الحجز ونسخة من مدة الخدمة. تغيير المدة لاحقًا لا يعيد حساب نهاية الحجوزات القائمة.
- تعديلات الجدول لا يجوز أن تتعارض مع الحجوزات النشطة. البيانات التاريخية الناقصة قد تتطلب مراجعة قبل التعديل.

راجع [تفاصيل الجدولة](SCHEDULING.md) لمزيد من المعلومات. يمكن فحص بيانات الجدولة بأمر القراءة فقط:

```bash
php artisan scheduling:audit --no-interaction
```

## هيكل المشروع

| المسار | المسؤولية |
| --- | --- |
| `app/Actions` | تنفيذ عمليات العمل مثل إنشاء الحجز وتحديث الجدول |
| `app/Data` | كائنات نقل البيانات |
| `app/Queries` | استعلامات القوائم والتصفية |
| `app/Models` / `app/Enums` | نماذج البيانات والأدوار والحالات |
| `app/Http/Controllers` | نقاط الدخول للويب وAPI |
| `app/Http/Requests` / `app/Http/Resources` | التحقق من المدخلات وتنسيق استجابات API |
| `app/Policies` | صلاحيات الوصول للموارد |
| `resources` | قوالب Blade وملفات CSS وJavaScript |
| `database` | الترحيلات وFactories وSeeders |
| `routes` | مسارات الويب وAPI والمهام المجدولة |
| `tests` | اختبارات الوحدة والميزات |
| `docker` / `infra` | إعدادات الحاويات والنشر والبنية التحتية |

## الاختبارات

الاختبارات الافتراضية تستخدم SQLite في الذاكرة، وتستبدل البريد والطوابير والكاش بإعدادات اختبار كما في [phpunit.xml](phpunit.xml).

```bash
php artisan test --compact --no-interaction
```

لتشغيل اختبار محدد:

```bash
php artisan test --compact tests/Feature/Api/V1/BookingControllerTest.php --no-interaction
```

داخل Docker، أضف `docker compose -f compose.yaml -f compose.dev.yaml exec app` قبل أمر PHP.

اختبارات التزامن الخاصة بـ MySQL تحتاج قاعدة اختبار منفصلة؛ يستخدم CI قاعدة مخصصة لذلك. لا توجّه الاختبارات لقاعدة التطوير أو الإنتاج التي تحتوي بيانات مطلوبة.

عند تعديل PHP، شغّل التنسيق قبل تقديم التغيير:

```bash
vendor/bin/pint --dirty --format agent
```

## التشغيل والنشر

[GitHub Actions](.github/workflows/ci-cd.yml) يحتوي على فحص إعدادات النشر، واختبارات PHP وMySQL، وبناء الواجهة وصور الإنتاج. الدفع إلى `main` أو التشغيل اليدوي عليه يفعّل أيضًا مسار النشر إلى AWS بعد نجاح الفحوصات؛ يحتاج ذلك موارد AWS وصلاحيات OIDC المهيأة للمشروع.

- [Dockerfile الإنتاج](docker/Dockerfile.prod): بناء صور التطبيق وNginx.
- [إعداد EC2](docker/compose.ec2.yaml) و[إعداد Auto Scaling](docker/compose.autoscaling.yaml): تكوين خدمات الإنتاج.
- يجب تشغيل Horizon لمعالجة الطوابير وتشغيل Laravel scheduler لتنفيذ المهام الدورية.
- [المهام المجدولة](routes/console.php) تشمل إحصاءات Horizon كل خمس دقائق وتنظيف مفاتيح idempotency المنتهية كل ساعة.
- أمر `php artisan app:check-scaling-readiness --no-interaction` يفحص متطلبات الحالة المشتركة في بيئة Auto Scaling: RDS وRedis المشفّر وتخزين صور S3. هذا فحص لبيئة الإنتاج المشتركة وليس شرطًا للتشغيل المحلي.

## حل المشكلات

| المشكلة | ما يجب مراجعته |
| --- | --- |
| فشل اتصال MySQL أو Redis | أسماء المضيف والمنافذ تختلف بين داخل Docker والتشغيل على الجهاز |
| ملفات الواجهة غير ظاهرة أو Vite manifest مفقود | شغّل `npm run build` أو أبقِ `npm run dev` يعمل |
| صور المراكز لا تظهر | راجع `CENTER_IMAGES_DISK` ورابط `public/storage` عند استخدام `public` |
| رسائل البريد لا تصل محليًا | `MAIL_MAILER=log` يكتب الرسائل في السجلات؛ راجع مخرجات التطبيق |
| مهام الطابور لا تُنفذ | راجع خدمة Horizon واتصال Redis |
| الحجز مرفوض رغم أن المركز مفتوح | راجع مدة الخدمة والسعة والحجوزات المتداخلة والاستثناءات |

لمراجعة سجلات الخدمات محليًا:

```bash
docker compose -f compose.yaml -f compose.dev.yaml logs --tail=100 app horizon nginx
```
