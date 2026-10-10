# مواعيد المراكز، مدد الخدمات، والاستثناءات

## نتيجة التنفيذ والفحص المحلي — 9 أكتوبر 2026

تم فحص MySQL المحلية عبر Laravel Boost قبل تطبيق أي ترحيل. المحرك الفعلي MySQL 8.4.11، وإصدار Laravel المثبت 13.31.0.

| المؤشر قبل الترحيل | العدد |
| --- | ---: |
| الخدمات في الكتالوج | 8 |
| ارتباطات الخدمة بالمركز بلا مدة | 13 |
| الحجوزات النشطة بلا مدة | 1 |
| الحجوزات النشطة بتوقيت غير مؤكد المصدر | 1 |
| إجمالي الحجوزات التاريخية | 2 |
| المراكز | 3 |
| سجلات ساعات العمل المتناقضة | 0 |

طُبقت الترحيلات الثلاثة الجديدة فقط على قاعدة `mothoq` المحلية. لم تُنفذ ترحيلات أخرى معلقة، ولم يتم نشر التطبيق. قورنت بصمة SHA-256 لجميع الحقول القديمة للحجزين قبل الترحيل وبعده، وكانت متطابقة. بقيت جميع مدد الحجوزات القديمة ومعلومات مصدر توقيتها NULL. لم تتغير حالات الحجوزات أو مواعيدها، ولم تُملأ مدد الخدمات تلقائيًا.

## التصميم والـSchema

يستمر استخدام العلاقة الفعلية `service_service_center` والجدول الأسبوعي الموجود `opening_hours`؛ لا توجد علاقة أو نسخة جدول أسبوعي مكررة.

| الجدول | التغيير والقيود |
| --- | --- |
| `service_service_center` | إضافة `duration_minutes` كعدد دقائق صحيح موجب قابل لـNULL؛ من 1 إلى 1439. يستمر قيد التفرد الحالي على الخدمة والمركز. |
| `bookings` | إضافة `duration_minutes` كنسخة ثابتة من مدة الخدمة وقت إنشاء الحجز، قابلة لـNULL للتاريخ القديم. إضافة `scheduled_at_timezone`، إما `UTC` للحجوزات الجديدة أو NULL للتوقيت التاريخي غير المؤكد. |
| `service_centers` | إضافة `timezone` بقيمة ابتدائية `Africa/Cairo`. يمكن المالك أو المسؤول تحديثها من واجهة تحديث المركز الحالية، بعد فحص أثر التغيير على الحجوزات النشطة. |
| `opening_hours` | الإبقاء على التفرد `(service_center_id, day_of_week)` والمفتاح الخارجي الحالي. إضافة قيود الأيام ونطاق الوقت. تمثيل الأيام هو أسماء إنجليزية صغيرة مثل `friday` وفق `DayOfWeek` الموجود. |
| `service_center_schedule_exceptions` | `id`, `service_center_id`, `date`, `opens_at`, `closes_at`, `is_closed`, timestamps. مفتاح خارجي يحذف الاستثناء عند حذف المركز فعليًا، وتفرد `(service_center_id, date)`، وقيود نطاق الوقت. |

اليوم المغلق لا يحمل وقت فتح أو إغلاق. اليوم المفتوح يحتاج الوقتين، والفتح قبل الإغلاق في اليوم نفسه؛ لا تدعم النسخة الحالية فترات متعددة أو ساعات تمتد لليوم التالي. الـAPI يقبل ساعات الجدول بصيغة `HH:MM`.

تُطبق القيود بـCHECK في MySQL 8.4، وبتريغرز INSERT/UPDATE في SQLite المستخدمة للاختبار، لأن إضافة CHECK لجدول SQLite موجود تتطلب إعادة بنائه. تتحقق ترحيلة القيود من البيانات قبل الإضافة، وتفشل برسالة مراجعة بدل إصلاح السجلات القديمة تلقائيًا. الترحيلات موجهة للمحركين المستخدمين؛ يلزم تكييفها قبل تشغيلها على محرك آخر.

## حساب الموعد وحماية الحجوزات

- `ResolveScheduleAction` يحدد تاريخ المركز المحلي، ثم يبحث عن الاستثناء، ثم عن يوم الأسبوع. غياب الجدول يعني عدم الإتاحة.
- `LocalBookingTime` يفسر الوقت غير المصحوب بإزاحة بتوقيت المركز. الوقت المصحوب بـ`Z` أو إزاحة مثل `+03:00` يمثل لحظة فعلية ويُحوّل إلى UTC.
- الطلب يقبل `YYYY-MM-DDTHH:MM` أو وقتًا بالثواني، مع إزاحة اختيارية. يدعم الكسور الصفرية التي يخرجها الـAPI؛ لا تُقرّب كسور الثانية غير الصفرية ضمنيًا لأن عمود التخزين بدقة الثواني.
- الوقت المحلي غير الموجود أو المتكرر أثناء انتقال التوقيت الصيفي يُرفض. يلزم موعد بإزاحة صريحة لتحديد اللحظة. إذا كان حد فتح/إغلاق الجدول نفسه ملتبسًا، يبقى ذلك اليوم غير متاح للحجز إلى أن يُضبط حد واضح، ويمكن استخدام استثناء لذلك اليوم.
- `CreateBookingAction` يجلب مدة الخدمة من علاقة المركز داخل المعاملة، ولا يأخذ `duration_minutes` أو `scheduled_at_timezone` من العميل.
- البداية يجب أن تكون في المستقبل وداخل الفترة؛ النهاية = البداية الفعلية بـUTC + مدة الحجز بالدقائق. يسمح بانتهاء الحجز تمامًا عند الإغلاق، ولا يسمح بتجاوز الإغلاق أو الانتقال ليوم محلي آخر.
- تغيير مدة الخدمة يطبق على الحجوزات التالية فقط؛ لا يغير نسخة مدة أي حجز موجود.
- جميع تغييرات الجدول والاستثناءات، وحذف الاستثناء، وتغيير المنطقة الزمنية، تخضع لفحص الحجوزات النشطة `pending` و`accepted` داخل نفس المعاملة. التعارض يعيد 422 ويتراجع عن التعديل بالكامل.
- وقت تاريخي غير مؤكد لحجز نشط يمنع تغيير الجدول لأن تاريخ الحجز المحلي غير معلوم. مدة غير معلومة تمنع تغييرات الجدول المؤثرة على تاريخه المعروف. المراجعة تشمل كل الحجوزات النشطة، حتى لو كان تاريخها قديمًا؛ لا نغير الحالة أو نتجاهل حجزًا نشطًا تلقائيًا.
- الحجوزات المكتملة والملغاة والمرفوضة لا تمنع تعديل الجدول، وتظل بياناتها التاريخية كما هي.

كل عمليات كتابة الحجوزات والجداول ومدد الخدمات وتوقيت المركز تقفل صف المركز نفسه داخل معاملة، مع إعادة محاولة تعارضات المعاملة حتى ثلاث مرات. قيود التفرد موجودة في قاعدة البيانات، وتحديث استثناء التاريخ يستخدم `updateOrCreate` ليحافظ على `id` و`created_at`. الأخطاء المتزامنة المتبقية تعاد كـ409 مع رسالة إعادة المحاولة، بدل كشف SQL للعميل.

تبقى حماية منع الحجز النشط المكرر لنفس العميل والمركز واللحظة الفعلية موجودة، بما في ذلك الطلبات التي تمثل اللحظة نفسها بإزاحات مختلفة. لم يُضف افتراض أن المركز يستوعب سيارة واحدة فقط، ولا نموذج سعة أو موارد أو منع تداخل بين جميع العملاء؛ هذه سياسة غير موجودة في المشروع وتتطلب قرارًا مستقلًا.

## الـAPI

المسارات التالية نسبية إلى أصل التطبيق. المسارات الإدارية تستخدم رقم المركز، والمسار العام يستخدم `slug`. تظل مصادقة Sanctum والأدوار والسياسات الحالية سارية، ولا يستطيع المالك الوصول لمركز مالك آخر؛ ترجع المحاولة 404. الحساب العادي لا يدير الجداول.

استبدل `{role}` بـ`owner` أو `admin`:

| الطريقة | المسار | الغرض |
| --- | --- | --- |
| GET | `/api/v1/service-centers/{slug}` | تفاصيل المركز، المنطقة الزمنية، الجدول الأسبوعي، ومدد الخدمات المرتبطة. |
| GET | `/api/v1/service-centers/{slug}/schedule?date=2026-09-25` | الجدول الفعلي للتاريخ المحلي المختار. |
| PATCH | `/api/v1/{role}/service-centers/{id}/opening-hours` | الواجهة الموجودة لتحديث أيام الأسبوع السبعة. |
| GET | `/api/v1/{role}/service-centers/{id}/schedule-exceptions` | قائمة الاستثناءات مرتبة بالتاريخ، 31 سجلًا بالصفحة. |
| PUT | `/api/v1/{role}/service-centers/{id}/schedule-exceptions` | إنشاء أو تحديث استثناء باستخدام `date`؛ 201 للإنشاء و200 للتحديث. |
| DELETE | `/api/v1/{role}/service-centers/{id}/schedule-exceptions/{exceptionId}` | حذف الاستثناء والعودة للجدول الأسبوعي إذا لم يتعارض ذلك مع حجز نشط؛ 204 عند النجاح. |
| PATCH | `/api/v1/{role}/service-centers/{id}/services/{serviceId}/duration` | تعديل مدة خدمة مرتبطة فعلًا بالمركز؛ لا يُنشئ ارتباطًا جديدًا. |
| PATCH | `/api/v1/{role}/service-centers/{id}` | تحديث `timezone` باستخدام الواجهة الحالية. |
| POST | `/api/v1/service-centers/{slug}/bookings` | إنشاء حجز باستخدام المدة الموثوقة من قاعدة البيانات. |

تعديل مدة خدمة:

```json
{"duration_minutes": 45}
```

يمكن إرسال `null` لإيقاف حجوزاتها الجديدة لحين تحديد المدة. الحدود الصحيحة 1–1439 دقيقة. مثال جزء الاستجابة:

```json
{"data":{"id":4,"name":"فحص المحرك","slug":"engine-check","duration_minutes":45}}
```

استثناء إغلاق:

```json
{"date":"2026-09-25","is_closed":true,"opens_at":null,"closes_at":null}
```

استثناء ساعات مختلفة، يفتح يومًا مغلقًا أو يغير ساعات يوم مفتوح:

```json
{"date":"2026-09-25","is_closed":false,"opens_at":"10:00","closes_at":"14:00"}
```

استجابة الاستثناء:

```json
{"data":{"id":12,"date":"2026-09-25","is_closed":false,"opens_at":"10:00","closes_at":"14:00"}}
```

مثال استجابة الجدول الفعلي لهذا الاستثناء:

```json
{
  "data": {
    "is_available": true,
    "opens_at_utc": "2026-09-25T07:00:00.000000Z",
    "closes_at_utc": "2026-09-25T11:00:00.000000Z",
    "date": "2026-09-25",
    "timezone": "Africa/Cairo",
    "source": "exception",
    "is_closed": false,
    "opens_at": "10:00",
    "closes_at": "14:00"
  }
}
```

`source` إحدى `exception`, `weekly`, `unconfigured`. الإتاحة هنا تصف فترة العمل؛ لا تضمن توفر خدمة بمدة محددة أو سعة حجوزات. لا تُعرض المراكز غير المنشورة عبر المسار العام.

طلب الحجز:

```json
{"service_id":4,"customer_phone":"01012345678","scheduled_at":"2026-09-25T10:00:00+03:00"}
```

جزء الاستجابة بعد حفظ مدة 45 دقيقة:

```json
{
  "data": {
    "scheduled_at": "2026-09-25T07:00:00.000000Z",
    "scheduled_at_timezone": "UTC",
    "duration_minutes": 45,
    "ends_at": "2026-09-25T07:45:00.000000Z",
    "schedule_requires_review": false
  }
}
```

الحجز التاريخي غير المؤكد يعيد قيمة `scheduled_at` القديمة دون إضافة `Z`، مع `scheduled_at_timezone: null`، و`ends_at: null`، و`schedule_requires_review: true`. تعرض الواجهات والبريد المواعيد المؤكدة بتوقيت المركز، وتوضح أن التوقيت القديم يحتاج مراجعة. تبقى مرشحات تاريخ قائمة الحجوزات مطابقة للتاريخ المخزن، أي UTC للحجوزات الجديدة؛ تم توضيح ذلك في عنوان الحقل.

الجدول الأسبوعي يحتفظ بعقده الحالي: مصفوفة `opening_hours` من سبعة عناصر، لكل منها `day`, `is_closed`, `opens_at`, `closes_at`. على سبيل المثال يمثل عنصر الجمعة المفتوح `{"day":"friday","is_closed":false,"opens_at":"09:00","closes_at":"18:00"}`. إدخال اليوم المغلق في الواجهة القديمة يُطبّع إلى وقتين NULL؛ واجهة الاستثناء الجديدة ترفض الأوقات المتناقضة مع الإغلاق.

## البيانات القديمة والقرارات التي تتطلب معلومات فعلية

1. يجب تحديد مدد الارتباطات الـ13 بواسطة المراكز، ولا توجد مدة افتراضية. لذلك تظل الخدمات غير محددة المدة غير قابلة للحجز الجديد.
2. يجب مراجعة الحجز النشط الواحد مع مصدره/المركز لتحديد مدته الحقيقية ومعنى وقته المخزن. لا تكفي منطقة التطبيق الحالية لإثبات ما قصده إدخال قديم.
3. لا توجد عملية جماعية أو واجهة تلقائية تحول الحجوزات القديمة. بعد التحقق البشري، يلزم إجراء تصحيح محدد ومراجع لتسجيل المدة واللحظة UTC المؤكدة ومصدرها. لا يجوز تحويل سجلات NULL لمجرد إزالة رسالة المراجعة.
4. افتراض المراكز المصرية المعتمد هو `Africa/Cairo`، وتُقرأ الساعات الأسبوعية القديمة كأوقات محلية دون تغيير أرقامها. إذا كان مركز قد أدخل ساعاته القديمة على أساس UTC، يجب مراجعة جدوله صراحة؛ لا تتم إزاحة الأرقام تلقائيًا.
5. لم تُغير سياسات حالة الحجز أو الإلغاء أو السعة. إدارة الاستثناءات والمدد متاحة عبر الـAPI؛ لم تُنشأ واجهة إدارة جديدة.
6. توجد نسخة MySQL منفصلة باسم `mothoq_scheduling_test` للاختبارات. شُغلت حاوية MySQL المحلية الموجودة للوصول إلى البيانات. لم تُشغّل بقية خدمات التطبيق أو تُنشر هذه التغييرات.

أمر تدقيق للقراءة فقط يعمل قبل الترحيل وبعده:

```bash
php artisan scheduling:audit --no-interaction
```

عند التشغيل من المضيف خارج Docker في هذه البيئة، استُخدم `DB_HOST=127.0.0.1 DB_PORT=33061` دون تعديل ملف البيئة.

التراجع عن ترحيلات الأعمدة يحذف الحقول الجديدة وبيانات الاستثناءات؛ لا تستخدم rollback بعد اعتماد بيانات جديدة لمجرد إعادة تجربة الترحيل. اختُبر التراجع في قاعدة اختبار فقط.

## الاختبارات المنفذة

- المجموعة الأصلية من 32 حالة محفوظة ومشمولة بالتشغيل. أضيفت مدد صريحة لبيانات الحجوزات الاختبارية، وحدد UTC صراحة في السيناريوهات القديمة التي تعتمد عليه.
- تغير توقع الحالة التي تبدأ قبل الإغلاق بثانية إلى الرفض، لأنها لا تتسع للمدة الجديدة. حالتا ساعات العمل الناقصة تتحققان الآن من رفض البيانات بقاعدة البيانات بدل إنشاء صف غير صالح.
- صُححت بيانات اختبار الـdemo بحيث لا تضيف وقت فتح إلى يوم مغلق، مع استمرار اختبار حفظ تعديلات المستخدم.
- تشمل التغطية الحدود، ومدد الخدمات المختلفة، وثبات نسخة الحجز، وأولوية الاستثناءات، وحذفها وتحديثها دون تغيير هويتها، والصلاحيات، وتعارض الحجوزات، وفروق الصيف والشتاء، والتاريخ المحلي المختلف عن UTC، والتوقيت الصيفي الملتبس، وNULL التاريخي، والتكرارات والقيود، وترقية البيانات القديمة والتراجع عنها دون تغيير الحقول الأصلية.
- اختبار فشل كتابة متزامنة يحاكي خطأ قاعدة البيانات ويثبت استجابة 409 والتراجع. ليست هذه النتائج اختبار ضغط متعدد العمليات.

```bash
vendor/bin/pint --dirty --format agent
php artisan test --compact
```

النتيجة الكاملة: **335 اختبارًا ناجحًا، 1418 تحققًا**.

التحقق على MySQL 8.4 في قاعدة منفصلة:

```bash
DB_CONNECTION=mysql DB_HOST=127.0.0.1 DB_PORT=33061 \
DB_DATABASE=mothoq_scheduling_test DB_URL= \
php artisan test --compact \
  tests/Feature/ServiceCenterSchedulingTest.php \
  tests/Feature/ScheduleIntegrityTest.php \
  tests/Feature/BookingOpeningHoursTest.php \
  tests/Feature/Api/V1/BookingControllerTest.php \
  tests/Feature/Api/V1/Owner/OpeningHourControllerTest.php \
  tests/Feature/Api/V1/Admin/OpeningHourControllerTest.php \
  tests/Feature/Web/OpeningHourControllerTest.php \
  tests/Feature/Web/BookingControllerTest.php
```

النتيجة: **123 اختبارًا ناجحًا، 462 تحققًا**. لا تشغّل مجموعة الاختبارات على قاعدة التطبيق الفعلية؛ اختبارات تحديث قاعدة البيانات تعيد إنشاء الجداول.

## الملفات

القائمة أدناه تشمل ملفات هذه المهمة، وبعضها يحتوي أصلًا على تعديلات محلية جرى الحفاظ عليها. لا تشمل ملف `PublishedDemoSeeder.php` السابق لأنه لم يُغير في هذه المهمة.
- `.ai/rules/app.md`
- `.ai/rules/database.md`
- `.ai/rules/index.md`
- `app/Actions/Bookings/CancelBookingAction.php`
- `app/Actions/Bookings/CreateBookingAction.php`
- `app/Actions/Bookings/UpdateBookingStatusAction.php`
- `app/Actions/ServiceCenters/DeleteScheduleExceptionAction.php`
- `app/Actions/ServiceCenters/ResolveScheduleAction.php`
- `app/Support/LocalBookingTime.php`
- `app/Actions/ServiceCenters/UpdateOpeningHoursAction.php`
- `app/Actions/ServiceCenters/UpdateScheduleExceptionAction.php`
- `app/Actions/ServiceCenters/UpdateServiceCenterAction.php`
- `app/Actions/ServiceCenters/UpdateServiceDurationAction.php`
- `app/Actions/ServiceCenters/ValidateScheduleBookingsAction.php`
- `app/Console/Commands/AuditScheduling.php`
- `app/Data/Bookings/CreateBookingData.php`
- `app/Data/ServiceCenters/UpdateServiceCenterData.php`
- `app/Http/Controllers/Api/V1/Admin/ScheduleExceptionController.php`
- `app/Http/Controllers/Api/V1/Admin/ServiceDurationController.php`
- `app/Http/Controllers/Api/V1/Owner/ScheduleExceptionController.php`
- `app/Http/Controllers/Api/V1/Owner/ServiceDurationController.php`
- `app/Http/Controllers/Api/V1/ScheduleController.php`
- `app/Http/Requests/Bookings/StoreBookingRequest.php`
- `app/Http/Requests/ServiceCenters/StoreScheduleExceptionRequest.php`
- `app/Http/Requests/ServiceCenters/UpdateServiceCenterRequest.php`
- `app/Http/Requests/ServiceCenters/UpdateServiceDurationRequest.php`
- `app/Http/Resources/Api/V1/BookingResource.php`
- `app/Http/Resources/Api/V1/OwnerServiceCenterResource.php`
- `app/Http/Resources/Api/V1/ScheduleExceptionResource.php`
- `app/Http/Resources/Api/V1/ServiceCenterResource.php`
- `app/Http/Resources/Api/V1/ServiceResource.php`
- `app/Models/Booking.php`
- `app/Models/Service.php`
- `app/Models/ServiceCenter.php`
- `app/Models/ServiceCenterScheduleException.php`
- `app/Queries/Bookings/AdminBookingQuery.php`
- `app/Queries/Bookings/CustomerBookingQuery.php`
- `app/Queries/Bookings/OwnerBookingQuery.php`
- `bootstrap/app.php`
- `database/factories/BookingFactory.php`
- `database/factories/ServiceCenterScheduleExceptionFactory.php`
- `database/migrations/2026_10_09_165800_add_scheduling_fields_to_service_centers_and_bookings.php`
- `database/migrations/2026_10_09_165801_create_service_center_schedule_exceptions_table.php`
- `database/migrations/2026_10_09_165802_add_schedule_integrity_constraints.php`
- `database/seeders/ServiceCenterScheduleExceptionSeeder.php`
- `resources/views/bookings/_filters.blade.php`
- `resources/views/bookings/_management-list.blade.php`
- `resources/views/bookings/index.blade.php`
- `resources/views/mail/bookings/booking-created-confirmation.blade.php`
- `resources/views/mail/bookings/new-booking-received.blade.php`
- `resources/views/mail/bookings/status-updated.blade.php`
- `resources/views/service-centers/_booking-form.blade.php`
- `routes/api.php`
- `tests/Feature/Api/V1/BookingControllerTest.php`
- `tests/Feature/BookingOpeningHoursTest.php`
- `tests/Feature/PublishedDemoSeederTest.php`
- `tests/Feature/ScheduleIntegrityTest.php`
- `tests/Feature/SchedulingMigrationTest.php`
- `tests/Feature/ServiceCenterSchedulingTest.php`
- `tests/Feature/Web/BookingControllerTest.php`

المراجع الفنية: [علاقات Laravel 13](https://laravel.com/framework/docs/13.x/eloquent-relationships)، [التحقق من المدخلات](https://laravel.com/framework/docs/13.x/validation)، [توثيق PHPUnit](https://phpunit.de/documentation.html).
