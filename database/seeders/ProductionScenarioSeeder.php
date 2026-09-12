<?php

declare(strict_types=1);

namespace Database\Seeders;

use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Rehla\Catalog\Actions\CreateService;
use Rehla\Catalog\Actions\PublishService;
use Rehla\Catalog\Data\CreateServiceData;
use Rehla\Forms\Actions\CreateFormDraft;
use Rehla\Forms\Actions\PublishFormVersion;
use Rehla\Forms\Data\FormFieldData;
use Rehla\Forms\Enums\FieldType;
use Rehla\Identity\Actions\RegisterCustomer;
use Rehla\Identity\Data\RegisterCustomerData;
use Rehla\Travelers\Actions\CreateTraveler;
use Rehla\Travelers\Data\TravelerData;
use Rehla\Travelers\Enums\Gender;
use Rehla\Wallet\Actions\OpenWallet;

class ProductionScenarioSeeder extends Seeder
{
    public function run(): void
    {
        $now = CarbonImmutable::now();

        // 1. Seed Abilities and Roles
        $abilities = [
            'services.manage' => 'Manage service catalog and prices',
            'forms.manage' => 'Manage dynamic application forms',
            'customers.view' => 'View customer directory and accounts',
            'travelers.view' => 'View traveler directory and profiles',
            'wallets.view' => 'Inspect customer wallets and ledger',
            'bank_accounts.manage' => 'Manage company bank accounts',
            'topups.review' => 'Review and approve/reject bank top-ups',
            'orders.view' => 'View customer orders and snapshots',
            'executions.manage' => 'Transition fulfillment service executions',
            'content.manage' => 'Manage CMS content blocks',
            'notifications.manage' => 'Inspect and resend outbox notifications',
            'roles.manage' => 'Manage staff roles, abilities and access',
            'audit.view' => 'Inspect immutable platform audit trail',
            'reporting.view' => 'View analytics and operational reports',
        ];

        $abilityIds = [];
        foreach ($abilities as $name => $label) {
            $existing = DB::table('abilities')->where('name', $name)->first();
            if ($existing === null) {
                $id = (string) Str::uuid();
                DB::table('abilities')->insert([
                    'id' => $id,
                    'name' => $name,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $abilityIds[$name] = $id;
            } else {
                $abilityIds[$name] = $existing->id;
            }
        }

        // Roles
        $roles = [
            'super_admin' => [
                'label' => 'Super Administrator',
                'abilities' => array_keys($abilities),
            ],
            'operations' => [
                'label' => 'Operations Specialist',
                'abilities' => ['executions.manage', 'orders.view', 'customers.view', 'travelers.view', 'reporting.view'],
            ],
            'finance' => [
                'label' => 'Finance Auditor',
                'abilities' => ['topups.review', 'bank_accounts.manage', 'wallets.view', 'reporting.view'],
            ],
        ];

        $roleIds = [];
        foreach ($roles as $name => $data) {
            $existing = DB::table('roles')->where('name', $name)->first();
            if ($existing === null) {
                $roleId = (string) Str::uuid();
                DB::table('roles')->insert([
                    'id' => $roleId,
                    'name' => $name,
                    'label' => $data['label'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            } else {
                $roleId = $existing->id;
            }
            $roleIds[$name] = $roleId;

            foreach ($data['abilities'] as $abilityName) {
                if (isset($abilityIds[$abilityName])) {
                    DB::table('role_ability')->insertOrIgnore([
                        'role_id' => $roleId,
                        'ability_id' => $abilityIds[$abilityName],
                    ]);
                }
            }
        }

        // 2. Staff Accounts
        $staffUsers = [
            [
                'name' => 'System Admin',
                'email' => 'admin@rehla.test',
                'password' => 'AdminSecret123!',
                'role' => 'super_admin',
            ],
            [
                'name' => 'Operations Lead',
                'email' => 'ops@rehla.test',
                'password' => 'StaffPass123!',
                'role' => 'operations',
            ],
            [
                'name' => 'Finance Officer',
                'email' => 'finance@rehla.test',
                'password' => 'StaffPass123!',
                'role' => 'finance',
            ],
        ];

        $firstAdminUserId = null;
        foreach ($staffUsers as $staff) {
            $existingUser = DB::table('users')->where('email', $staff['email'])->first();
            if ($existingUser === null) {
                $userId = (string) Str::uuid();
                DB::table('users')->insert([
                    'id' => $userId,
                    'name' => $staff['name'],
                    'email' => $staff['email'],
                    'password' => Hash::make($staff['password']),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            } else {
                $userId = $existingUser->id;
            }

            if ($firstAdminUserId === null) {
                $firstAdminUserId = $userId;
            }

            // Staff profile
            DB::table('staff_profiles')->updateOrInsert(
                ['user_id' => $userId],
                [
                    'id' => (string) Str::uuid(),
                    'mfa_confirmed_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );

            // Role assignment
            DB::table('user_role')->insertOrIgnore([
                'user_id' => $userId,
                'role_id' => $roleIds[$staff['role']],
            ]);
        }

        // 3. Company Bank Accounts
        $bankAccounts = [
            [
                'bank_name_en' => 'Bank of Khartoum',
                'bank_name_ar' => 'بنك الخرطوم',
                'account_number' => '1987654321',
                'beneficiary_name' => 'Rehla Travel Services Co.',
                'active' => true,
                'sort_order' => 1,
            ],
            [
                'bank_name_en' => 'Faisal Islamic Bank',
                'bank_name_ar' => 'بنك فيصل الإسلامي',
                'account_number' => '2847593021',
                'beneficiary_name' => 'Rehla Travel Services Co.',
                'active' => true,
                'sort_order' => 2,
            ],
            [
                'bank_name_en' => 'Omdurman National Bank',
                'bank_name_ar' => 'بنك أم درمان الوطني',
                'account_number' => '3948572019',
                'beneficiary_name' => 'Rehla Travel Services Co.',
                'active' => true,
                'sort_order' => 3,
            ],
        ];

        foreach ($bankAccounts as $acc) {
            $existing = DB::table('company_bank_accounts')->where('account_number', $acc['account_number'])->first();
            if ($existing === null) {
                DB::table('company_bank_accounts')->insert(array_merge($acc, [
                    'id' => (string) Str::uuid(),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]));
            }
        }

        // 4. Content Blocks
        $contentBlocks = [
            [
                'key' => 'home.hero.title',
                'title_en' => 'Fast, Reliable Visa & Travel Services for Sudanese Citizens',
                'title_ar' => 'خدمات التأشيرات والسفر السريعة والموثوقة للمواطنين السودانيين',
                'body_en' => 'Comprehensive online visa and travel operations for Sudanese citizens worldwide.',
                'body_ar' => 'عمليات تأشيرات وسفر شاملة عبر الإنترنت للمواطنين السودانيين حول العالم.',
                'status' => 'published',
                'published_at' => $now,
            ],
            [
                'key' => 'home.hero.subtitle',
                'title_en' => 'Apply Online',
                'title_ar' => 'التقديم عبر الإنترنت',
                'body_en' => 'Apply online, pay via local bank transfer, and track your application in real-time.',
                'body_ar' => 'قدّم طلبك عبر الإنترنت، وادفع عبر التحويل البنكي المحلي، وتابع طلبك خطوة بخطوة.',
                'status' => 'published',
                'published_at' => $now,
            ],
            [
                'key' => 'general.terms',
                'title_en' => 'Terms of Service',
                'title_ar' => 'شروط الخدمة',
                'body_en' => 'Rehla Terms of Service: All fees are non-refundable once processing begins.',
                'body_ar' => 'شروط خدمة رحلة: جميع الرسوم غير قابلة للاسترداد بمجرد بدء إجراءات الطلب.',
                'status' => 'published',
                'published_at' => $now,
            ],
            [
                'key' => 'general.privacy',
                'title_en' => 'Privacy Policy',
                'title_ar' => 'سياسة الخصوصية',
                'body_en' => 'Rehla Privacy Policy: We safeguard your personal identity and passport data with strict encryption.',
                'body_ar' => 'سياسة خصوصية رحلة: نحمي بيانات هويتك وجواز سفرك بتشفير صارم.',
                'status' => 'published',
                'published_at' => $now,
            ],
        ];

        foreach ($contentBlocks as $block) {
            DB::table('content_blocks')->updateOrInsert(
                ['key' => $block['key']],
                array_merge($block, [
                    'id' => (string) Str::uuid(),
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
            );
        }

        // 5. Services & Forms
        $servicesToCreate = [
            [
                'slug' => 'uae-tourist-visa-30-days',
                'nameEn' => 'UAE Tourist Visa (30 Days)',
                'nameAr' => 'تأشيرة سياحة الإمارات (30 يوم)',
                'shortDescriptionEn' => 'Fast 30-day single entry tourist visa for United Arab Emirates.',
                'shortDescriptionAr' => 'تأشيرة سياحية دخول لمرة واحدة لمدة 30 يوماً إلى دولة الإمارات العربية المتحدة.',
                'detailedDescriptionEn' => 'Comprehensive visa processing including health insurance and document verification. Turnaround time 2-3 business days.',
                'detailedDescriptionAr' => 'معالجة تأشيرة شاملة للتأمين الصحي وتدقيق المستندات مع وقت إنجاز من يومين إلى 3 أيام عمل.',
                'expectedDurationEn' => '2-3 Business Days',
                'expectedDurationAr' => '2 - 3 أيام عمل',
                'priceMinor' => 75_000_00, // 75,000 SDG
                'fields' => [
                    new FormFieldData(
                        key: 'visit_purpose',
                        type: FieldType::ShortText,
                        labelEn: 'Purpose of Visit',
                        labelAr: 'الغرض من الزيارة',
                        order: 1,
                        required: true,
                    ),
                    new FormFieldData(
                        key: 'arrival_date',
                        type: FieldType::Date,
                        labelEn: 'Expected Arrival Date',
                        labelAr: 'تاريخ الوصول المتوقع',
                        order: 2,
                        required: true,
                    ),
                    new FormFieldData(
                        key: 'contact_number',
                        type: FieldType::Phone,
                        labelEn: 'WhatsApp Contact Number',
                        labelAr: 'رقم الواتساب للتواصل',
                        order: 3,
                        required: true,
                    ),
                    new FormFieldData(
                        key: 'notes',
                        type: FieldType::LongText,
                        labelEn: 'Additional Notes',
                        labelAr: 'ملاحظات إضافية',
                        order: 4,
                        required: false,
                    ),
                ],
            ],
            [
                'slug' => 'saudi-umrah-permit',
                'nameEn' => 'Saudi Umrah Permit & Visa',
                'nameAr' => 'تصريح وتأشيرة العمرة للمملكة العربية السعودية',
                'shortDescriptionEn' => 'Official Umrah entry permit with Nusuk platform integration assistance.',
                'shortDescriptionAr' => 'تصريح دخول رسمي للعمرة مع مساعدة التسجيل في منصة نسك.',
                'detailedDescriptionEn' => 'Facilitates your spiritual journey with verified documentation, accommodation booking verification, and health guidelines.',
                'detailedDescriptionAr' => 'تسهيل رحلتك المباركة بتوثيق المستندات والتحقق من حجز الإقامة والإرشادات الصحية.',
                'expectedDurationEn' => '3-5 Business Days',
                'expectedDurationAr' => '3 - 5 أيام عمل',
                'priceMinor' => 120_000_00, // 120,000 SDG
                'fields' => [
                    new FormFieldData(
                        key: 'pilgrim_type',
                        type: FieldType::ShortText,
                        labelEn: 'Pilgrim Category',
                        labelAr: 'فئة المعتمر',
                        order: 1,
                        required: true,
                    ),
                    new FormFieldData(
                        key: 'travel_date',
                        type: FieldType::Date,
                        labelEn: 'Planned Travel Date',
                        labelAr: 'تاريخ السفر المخطط',
                        order: 2,
                        required: true,
                    ),
                    new FormFieldData(
                        key: 'emergency_contact',
                        type: FieldType::Phone,
                        labelEn: 'Emergency Contact Phone',
                        labelAr: 'رقم هاتف الطوارئ',
                        order: 3,
                        required: true,
                    ),
                ],
            ],
            [
                'slug' => 'egypt-entry-visa',
                'nameEn' => 'Egypt Entry Visa (Express)',
                'nameAr' => 'تأشيرة دخول جمهورية مصر (سريعة)',
                'shortDescriptionEn' => 'Fast entry visa authorization for Sudanese passport holders to Egypt.',
                'shortDescriptionAr' => 'موافقة تأشيرة دخول سريعة لحاملي الجواز السوداني إلى مصر.',
                'detailedDescriptionEn' => 'Direct embassy coordination for expedited approvals with official entry clearance.',
                'detailedDescriptionAr' => 'تنسيق مباشر مع السفارة للحصول على الموافقات العاجلة والتصاريح الرسمية.',
                'expectedDurationEn' => '5-7 Business Days',
                'expectedDurationAr' => '5 - 7 أيام عمل',
                'priceMinor' => 95_000_00, // 95,000 SDG
                'fields' => [
                    new FormFieldData(
                        key: 'border_crossing',
                        type: FieldType::ShortText,
                        labelEn: 'Border Port of Entry',
                        labelAr: 'منفذ الدخول المخطط',
                        order: 1,
                        required: true,
                    ),
                    new FormFieldData(
                        key: 'stay_length',
                        type: FieldType::Number,
                        labelEn: 'Length of Stay (Days)',
                        labelAr: 'مدة الإقامة (أيام)',
                        order: 2,
                        required: true,
                    ),
                ],
            ],
        ];

        $actorId = $firstAdminUserId ?? (string) Str::uuid();

        foreach ($servicesToCreate as $svc) {
            $existingService = DB::table('services')->where('slug', $svc['slug'])->first();
            if ($existingService === null) {
                $serviceData = app(CreateService::class)->execute(new CreateServiceData(
                    slug: $svc['slug'],
                    nameEn: $svc['nameEn'],
                    nameAr: $svc['nameAr'],
                    shortDescriptionEn: $svc['shortDescriptionEn'],
                    shortDescriptionAr: $svc['shortDescriptionAr'],
                    detailedDescriptionEn: $svc['detailedDescriptionEn'],
                    detailedDescriptionAr: $svc['detailedDescriptionAr'],
                    expectedDurationEn: $svc['expectedDurationEn'],
                    expectedDurationAr: $svc['expectedDurationAr'],
                    priceMinor: $svc['priceMinor'],
                    requirements: [
                        ['text_en' => 'Valid passport (minimum 6 months)', 'text_ar' => 'جواز سفر ساري المفعول (6 أشهر كحد أدنى)', 'sort_order' => 1],
                        ['text_en' => 'Recent passport-size photo', 'text_ar' => 'صورة شخصية حديثة بحجم الجواز', 'sort_order' => 2],
                    ],
                    media: [
                        ['document_id' => (string) Str::uuid(), 'alt_en' => $svc['nameEn'], 'alt_ar' => $svc['nameAr'], 'sort_order' => 1],
                    ],
                ), actorId: $actorId);

                $published = app(PublishService::class)->execute($serviceData->id, actorId: $actorId);

                $draft = app(CreateFormDraft::class)->execute($published->id, $svc['fields'], actorId: $actorId);
                app(PublishFormVersion::class)->execute($draft->id, actorId: $actorId);
            }
        }

        // 6. Demo Customer
        $customerEmail = 'customer@rehla.test';
        $existingCustomer = DB::table('users')->where('email', $customerEmail)->first();
        if ($existingCustomer === null) {
            $customer = app(RegisterCustomer::class)->handle(new RegisterCustomerData(
                name: 'Mohammed Ahmed',
                email: $customerEmail,
                password: 'CustomerPass123!',
            ));

            $wallet = app(OpenWallet::class)->execute($customer->id);

            // Fund customer wallet with 250,000 SDG for immediate checkout capability
            DB::table('wallets')->where('id', $wallet->id)->update([
                'balance_minor' => 250_000_00,
            ]);

            // Add Traveler
            app(CreateTraveler::class)->handle(new TravelerData(
                ownerId: $customer->id,
                fullName: 'Mohammed Ahmed Ali',
                dateOfBirth: '1990-01-15',
                gender: Gender::Male,
                passportNumber: 'P01928374',
                passportIssuedAt: '2021-06-01',
                passportExpiresAt: '2031-06-01',
            ));
        }
    }
}
