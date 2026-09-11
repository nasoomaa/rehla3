#!/usr/bin/env python3
import csv
import os

os.makedirs('docs/requirements', exist_ok=True)

rows = []

def add_row(aid, req, pkg, interface, db_inv, test_file, test_name, status="planned", evidence="", deferred=""):
    rows.append({
        "acceptance_id": aid,
        "source_requirement": req,
        "package": pkg,
        "interface": interface,
        "db_invariant": db_inv,
        "test_file": test_file,
        "test_name": test_name,
        "status": status,
        "evidence": evidence,
        "deferred_reason": deferred
    })

# R01-R06: Program & Architecture Baselines
add_row("R01.01", "R01", "Core", "PlatformBaseline", "single_database_modular_monolith", "tests/Feature/HostBootTest.php", "boots the Rehla host in testing mode")
add_row("R02.01", "R02", "Core", "SDGOnlyCurrency", "currency_must_be_sdg", "packages/Rehla/Core/tests/Unit/MoneyTest.php", "rejects currency mismatch and negative construction")
add_row("R03.01", "R03", "Core", "PackageBoundaries", "no_cross_package_cycles", "tests/Architecture/PackageDependencyTest.php", "enforces package dependency rules")
add_row("R04.01", "R04", "Core", "AtomicAcceptanceRegister", "all_atomic_requirements_registered", "tests/Architecture/AcceptanceRegisterTest.php", "maps every product section and mandatory atomic family")
add_row("R05.01", "R05", "Core", "PostgresTestingSafety", "integration_db_ends_with_testing", "tests/Architecture/TestingDatabaseGuardTest.php", "rejects an unsafe integration database")
add_row("R06.01", "R06", "Core", "PackageDiscovery", "all_19_packages_registered", "tests/Architecture/PackageDiscoveryTest.php", "discovers every declared Rehla package provider")

# R07: Service Catalog
add_row("R07.01", "R07", "Catalog", "ServiceCatalogQuery", "services_catalog_active_only", "packages/Rehla/Catalog/tests/Unit/ServiceLifecycleTest.php", "lists active services")
add_row("R07.02", "R07", "Catalog", "CreateServiceAction", "unique_service_code", "packages/Rehla/Catalog/tests/Unit/ServiceLifecycleTest.php", "enforces unique service code")

# R08.01-R08.11: Form Field Types & Attributes
fields = [
    ("R08.01", "text", "text field string validation"),
    ("R08.02", "textarea", "textarea string multiline validation"),
    ("R08.03", "number", "numeric range integer validation"),
    ("R08.04", "date", "iso date format and past/future validation"),
    ("R08.05", "select", "static options in-list validation"),
    ("R08.06", "radio", "single selection in-list validation"),
    ("R08.07", "checkbox", "boolean true/false validation"),
    ("R08.08", "file", "document upload mime and size validation"),
    ("R08.09", "phone", "sudanese phone format validation"),
    ("R08.10", "email", "rfc compliant email validation"),
    ("R08.11", "passport", "normalized passport uppercase alphanumeric validation"),
]
for aid, ftype, desc in fields:
    add_row(aid, "R08", "Forms", f"FormField::{ftype}", "form_field_type_valid", "packages/Rehla/Forms/tests/Unit/FieldValidationTest.php", desc)

# R09: Form Versioning
add_row("R09.01", "R09", "Forms", "PublishFormVersionAction", "published_form_version_immutable", "packages/Rehla/Forms/tests/Unit/PublishedFormImmutabilityTest.php", "prevents mutation of published form versions")

# R10: Account Shell & Web Auth
add_row("R10.01", "R10", "Identity", "CustomerRegisterAction", "unique_phone_or_email", "packages/Rehla/Identity/tests/Unit/AccountIsolationTest.php", "enforces account isolation and unique credential")

# R11-R12: Travelers & Passport Uniqueness
add_row("R11.01", "R11", "Travelers", "SaveTravelerAction", "traveler_belongs_to_account", "packages/Rehla/Travelers/tests/Unit/TravelerOwnershipTest.php", "ensures travelers are isolated to owner account")
add_row("R12.01", "R12", "Travelers", "PassportNormalizer", "unique_normalized_passport", "packages/Rehla/Travelers/tests/Unit/PassportUniquenessTest.php", "enforces unique normalized passport number")

# R13-R14: Orders & Snapshots
add_row("R13.01", "R13", "Orders", "OrderSnapshotContract", "order_and_traveler_snapshot_immutable", "packages/Rehla/Orders/tests/Unit/OrderImmutabilityTest.php", "verifies immutable order snapshots")
add_row("R14.01", "R14", "Purchasing", "SubmitOrderAction", "atomic_wallet_deduction_and_order_create", "packages/Rehla/Purchasing/tests/Unit/SubmitOrderTest.php", "executes atomic order submission and wallet debit")

# R15: Wallet Ledger
add_row("R15.01", "R15", "Wallet", "AppendLedgerEntryAction", "ledger_immutable_and_non_negative_balance", "packages/Rehla/Wallet/tests/Unit/WalletLedgerTest.php", "asserts wallet balance equals ledger sum without negative balance")

# R16-R21: Bank Accounts & TopUps
add_row("R16.01", "R16", "TopUps", "SubmitTopUpAction", "topup_request_status_pending", "packages/Rehla/TopUps/tests/Unit/SubmitTopUpTest.php", "creates pending top up request with receipt")
add_row("R17.01", "R17", "TopUps", "CompanyBankAccountQuery", "bank_account_is_active", "packages/Rehla/TopUps/tests/Unit/BankAccountTest.php", "retrieves active company bank accounts")
add_row("R18.01", "R18", "TopUps", "UploadReceiptAction", "receipt_in_private_storage", "packages/Rehla/TopUps/tests/Unit/SubmitTopUpTest.php", "verifies receipt stored in private disk")
add_row("R19.01", "R19", "TopUps", "ApproveTopUpAction", "atomic_approval_and_wallet_credit", "packages/Rehla/TopUps/tests/Unit/ApproveTopUpTest.php", "credits wallet exactly once on top up approval")
add_row("R20.01", "R20", "TopUps", "ApproveTopUpAction", "prevent_concurrent_double_approval", "packages/Rehla/TopUps/tests/Unit/ConcurrentApprovalTest.php", "rejects concurrent second approval of same top up")
add_row("R21.01", "R21", "TopUps", "RejectTopUpAction", "rejected_status_with_reason", "packages/Rehla/TopUps/tests/Unit/RejectTopUpTest.php", "rejects top up with required rejection reason")

# R22-R23: Public Catalog & Web Experience
add_row("R22.01", "R22", "Purchasing", "ValidatePurchaseRequirementsQuery", "service_must_be_active_to_purchase", "packages/Rehla/Purchasing/tests/Unit/SubmitOrderTest.php", "verifies purchase validation rules")
add_row("R23.01", "R23", "Web", "CatalogBrowsePage", "public_access_no_auth_required", "packages/Rehla/Web/tests/Unit/PublicWebTest.php", "renders public catalog browse without auth")

# R24: WhatsApp Inquiry
add_row("R24.01", "R24", "Integrations", "WhatsAppInquiryLinkGenerator", "deep_link_format_sanitized", "packages/Rehla/Integrations/tests/Unit/WhatsAppInquiryLinkTest.php", "generates deep link with inquiry prefilled text")

# R25-R28: Customer Purchase Journeys
add_row("R25.01", "R25", "Web", "CheckoutWizard", "checkout_session_validated", "packages/Rehla/Web/tests/Unit/PurchaseJourneyTest.php", "validates checkout step progression")
add_row("R26.01", "R26", "Web", "TravelerSelectionStep", "traveler_ownership_verified", "packages/Rehla/Web/tests/Unit/PurchaseJourneyTest.php", "prevents selecting unowned traveler")
add_row("R27.01", "R27", "Forms", "DynamicFormEvaluator", "form_fields_match_published_schema", "packages/Rehla/Forms/tests/Unit/FieldValidationTest.php", "evaluates form fields against schema")
add_row("R28.01", "R28", "Web", "OrderReviewStep", "price_frozen_at_submission", "packages/Rehla/Web/tests/Unit/PurchaseJourneyTest.php", "confirms price freeze during review")

# R29-R33: Order Invariants & Idempotency
add_row("R29.01", "R29", "Purchasing", "SubmitOrderAction", "idempotency_key_enforced", "packages/Rehla/Purchasing/tests/Unit/SubmitOrderTest.php", "reusing idempotency key returns existing order")
add_row("R30.01", "R30", "Purchasing", "SubmitOrderAction", "insufficient_balance_rejected", "packages/Rehla/Purchasing/tests/Unit/SubmitOrderTest.php", "rejects order when wallet balance is insufficient")
add_row("R31.01", "R31", "Purchasing", "SubmitOrderAction", "pessimistic_wallet_lock", "packages/Rehla/Purchasing/tests/Unit/SubmitOrderConcurrencyTest.php", "locks wallet row to prevent race conditions")
add_row("R32.01", "R32", "Purchasing", "SubmitOrderAction", "transactional_rollback_on_failure", "packages/Rehla/Purchasing/tests/Unit/SubmitOrderRollbackTest.php", "rolls back entire transaction if execution fails")
add_row("R33.01", "R33", "Orders", "OrderRepository", "immutable_order_records", "packages/Rehla/Orders/tests/Unit/OrderImmutabilityTest.php", "ensures historical orders cannot be updated or deleted")

# R34-R38: Fulfillment & Customer Action Requests
add_row("R34.01", "R34", "Fulfillment", "CreateExecutionAction", "execution_linked_to_order", "packages/Rehla/Fulfillment/tests/Unit/CreateExecutionTest.php", "creates fulfillment execution upon paid order")
add_row("R35.01", "R35", "Fulfillment", "FulfillmentContracts", "order_execution_separation", "packages/Rehla/Fulfillment/tests/Unit/CreateExecutionTest.php", "separates order contract from execution lifecycle")
add_row("R36.01", "R36", "Fulfillment", "TransitionExecutionAction", "valid_state_machine_transition", "packages/Rehla/Fulfillment/tests/Unit/ExecutionStateMachineTest.php", "enforces fulfillment status transitions")
add_row("R37.01", "R37", "Fulfillment", "RequestCustomerAction", "action_request_stored_with_deadline", "packages/Rehla/Fulfillment/tests/Unit/CustomerActionResponseTest.php", "stores customer action request with timeout")
add_row("R38.01", "R38", "Web", "CustomerActionResponsePage", "action_response_updates_execution", "packages/Rehla/Web/tests/Unit/CustomerActionJourneyTest.php", "submits customer response and resumes execution")

# R39: Notifications & Outbox
add_row("R39.01", "R39", "Notifications", "SendNotificationAction", "in_app_notification_created", "packages/Rehla/Notifications/tests/Unit/InAppNotificationTest.php", "delivers in-app notification to customer")

# R40.01-R40.14: Admin Sections & Capability Matrix
admin_sections = [
    ("R40.01", "DashboardSection", "dashboard metrics view"),
    ("R40.02", "ServicesSection", "service catalog management"),
    ("R40.03", "FormsSection", "form version management"),
    ("R40.04", "CustomersSection", "customer account overview"),
    ("R40.05", "TravelersSection", "traveler directory view"),
    ("R40.06", "TopUpsSection", "bank top up approval workflow"),
    ("R40.07", "OrdersSection", "order history inspection"),
    ("R40.08", "ExecutionsSection", "fulfillment execution controls"),
    ("R40.09", "AuditSection", "audit log viewer"),
    ("R40.10", "ReportsSection", "operational reports"),
    ("R40.11", "SettingsSection", "system settings"),
    ("R40.12", "BankAccountsSection", "company bank accounts setup"),
    ("R40.13", "RolesSection", "staff role and capability matrix"),
    ("R40.14", "UsersSection", "staff user accounts management"),
]
for aid, sec, desc in admin_sections:
    add_row(aid, "R40", "Admin", f"AdminPanel::{sec}", "admin_capability_required", "packages/Rehla/Admin/tests/Unit/AdminCapabilityMatrixTest.php", desc)

# R41-R46: Admin Operations
add_row("R41.01", "R41", "Catalog", "UpdateServicePriceAction", "price_history_recorded", "packages/Rehla/Catalog/tests/Unit/ServiceLifecycleTest.php", "appends price change to history")
add_row("R42.01", "R42", "Forms", "CreateDraftFormAction", "draft_version_editable", "packages/Rehla/Forms/tests/Unit/FormPublishingTest.php", "allows editing only on draft form versions")
add_row("R43.01", "R43", "Admin", "AdminAuthorizationGuard", "mfa_required_for_sensitive_roles", "packages/Rehla/Admin/tests/Unit/AdminCapabilityMatrixTest.php", "enforces TOTP for administrative capabilities")
add_row("R44.01", "R44", "TopUps", "TopUpReviewQueue", "topup_review_lock", "packages/Rehla/TopUps/tests/Unit/ApproveTopUpTest.php", "locks top up during staff review")
add_row("R45.01", "R45", "Fulfillment", "AssignExecutionStaffAction", "assigned_staff_recorded", "packages/Rehla/Fulfillment/tests/Unit/ExecutionStateMachineTest.php", "records assigned staff on execution")
add_row("R46.01", "R46", "Audit", "LogAuditEventAction", "audit_log_immutable", "packages/Rehla/Audit/tests/Unit/AuditImmutabilityTest.php", "ensures audit log cannot be modified or deleted")

# R47: Staff Capabilities
add_row("R47.01", "R47", "Identity", "CapabilityManager", "capabilities_assigned_via_roles", "packages/Rehla/Identity/tests/Unit/AuthorizationTest.php", "validates granular staff capability assignment")

# R48: Document Privacy
add_row("R48.01", "R48", "Documents", "StorePrivateDocumentAction", "documents_private_by_default", "packages/Rehla/Documents/tests/Unit/DocumentLifecycleTest.php", "enforces private storage disk for all documents")

# R49: Content Management
add_row("R49.01", "R49", "Content", "ContentBlockQuery", "localized_content_blocks", "packages/Rehla/Content/tests/Unit/ContentManagementTest.php", "retrieves localized content blocks")

# R50.01-R50.05: Standard Error Codes
errors = [
    ("R50.01", "INSUFFICIENT_BALANCE", "insufficient balance problem details"),
    ("R50.02", "SERVICE_UNAVAILABLE", "service unavailable problem details"),
    ("R50.03", "PRICE_CHANGED", "price changed problem details"),
    ("R50.04", "FORM_VERSION_CHANGED", "form version changed problem details"),
    ("R50.05", "DUPLICATE_PASSPORT", "duplicate passport problem details"),
]
for aid, code, desc in errors:
    add_row(aid, "R50", "Core", f"ErrorCode::{code}", "rfc7807_problem_details", "packages/Rehla/Core/tests/Unit/ErrorCodeTest.php", desc)

# R51: Order and Execution Status Separation
add_row("R51.01", "R51", "Orders", "OrderStatusEnum", "order_status_paid_invariant", "packages/Rehla/Orders/tests/Unit/OrderStatusSeparationTest.php", "verifies order status separate from execution")

# R52.01-R52.13: Customer Journey Stages
journey_stages = [
    ("R52.01", "discovery", "browse catalog and service detail"),
    ("R52.02", "inquiry", "whatsapp inquiry generation"),
    ("R52.03", "register", "customer account registration"),
    ("R52.04", "login", "customer login and session establishment"),
    ("R52.05", "wallet_check", "view wallet balance and history"),
    ("R52.06", "topup_initiate", "view company bank accounts"),
    ("R52.07", "topup_submit", "submit bank deposit receipt"),
    ("R52.08", "traveler_add", "add saved traveler profile"),
    ("R52.09", "service_select", "select service and start order"),
    ("R52.10", "form_fill", "fill dynamic service form"),
    ("R52.11", "order_checkout", "pay order with wallet balance"),
    ("R52.12", "order_track", "track fulfillment status"),
    ("R52.13", "document_receive", "download final fulfillment output"),
]
for aid, stage, desc in journey_stages:
    add_row(aid, "R52", "Web", f"CustomerJourneyStage::{stage}", "customer_journey_valid", "packages/Rehla/Web/tests/Unit/CustomerJourneyStagesTest.php", desc)

# R53-R61: Core & Platform Guarantees
add_row("R53.01", "R53", "Orders", "FamilyBookingPolicy", "single_traveler_per_order_in_phase_1", "packages/Rehla/Orders/tests/Unit/OrderPolicyTest.php", "enforces one traveler per order limit")
add_row("R54.01", "R54", "Orders", "OrderSnapshotContract", "order_data_frozen_at_purchase", "packages/Rehla/Orders/tests/Unit/OrderImmutabilityTest.php", "asserts all order data frozen at purchase time")
add_row("R55.01", "R55", "Purchasing", "PriceConflictDetector", "reject_checkout_if_price_changed", "packages/Rehla/Purchasing/tests/Unit/SubmitOrderTest.php", "rejects submission if catalog price changed")
add_row("R56.01", "R56", "TopUps", "DuplicateDepositGuard", "transaction_reference_unique_per_bank", "packages/Rehla/TopUps/tests/Unit/SubmitTopUpTest.php", "rejects duplicate bank transaction reference")
add_row("R57.01", "R57", "TopUps", "ConcurrentApprovalGuard", "optimistic_lock_on_topup_review", "packages/Rehla/TopUps/tests/Unit/ConcurrentApprovalTest.php", "prevents simultaneous dual approval")
add_row("R58.01", "R58", "Purchasing", "ConcurrencyControl", "wallet_row_level_lock", "packages/Rehla/Purchasing/tests/Unit/SubmitOrderConcurrencyTest.php", "handles simultaneous order checkouts safely")
add_row("R59.01", "R59", "Purchasing", "IdempotentOrderSubmission", "unique_idempotency_key_per_account", "packages/Rehla/Purchasing/tests/Unit/SubmitOrderConcurrencyTest.php", "prevents double payment on network retry")
add_row("R60.01", "R60", "Core", "ArchitectureEnforcer", "zero_cyclic_dependencies", "tests/Architecture/PackageDependencyTest.php", "verifies architecture dependency compliance")
add_row("R61.01", "R61", "Web", "LocalizationEngine", "arabic_and_english_rtl_support", "packages/Rehla/Web/tests/Unit/LocalizationTest.php", "renders RTL layout for Arabic locale")

# R62.01-R62.12: Operational Metrics Definitions
metrics = [
    ("R62.01", "total_orders", "count of all paid orders"),
    ("R62.02", "gross_revenue_sdg", "sum of order minor amounts in SDG"),
    ("R62.03", "topup_volume_sdg", "sum of approved top up amounts"),
    ("R62.04", "topup_approval_time_avg", "average minutes from submission to approval"),
    ("R62.05", "order_fulfillment_time_avg", "average hours from order to completion"),
    ("R62.06", "topup_rejection_rate", "percentage of rejected top up requests"),
    ("R62.07", "active_customers_count", "distinct customer accounts with orders in 30 days"),
    ("R62.08", "repeat_purchase_rate", "percentage of customers with more than 1 order"),
    ("R62.09", "service_popularity_breakdown", "orders grouped by service code"),
    ("R62.10", "bank_topup_channel_share", "top up volume grouped by company bank"),
    ("R62.11", "action_request_rate", "percentage of executions requiring customer action"),
    ("R62.12", "system_outbox_lag_seconds", "maximum seconds between event and delivery"),
]
for aid, mname, desc in metrics:
    add_row(aid, "R62", "Reporting", f"MetricCalculator::{mname}", "metric_definition_valid", "packages/Rehla/Reporting/tests/Unit/ProductMetricsTest.php", desc)

# R63.W01-R63.W13: Web Customer Journey Test Vectors
web_journeys = [
    ("R63.W01", "browse_catalog_public"),
    ("R63.W02", "whatsapp_inquiry_button"),
    ("R63.W03", "account_registration"),
    ("R63.W04", "login_session"),
    ("R63.W05", "check_wallet_zero_balance"),
    ("R63.W06", "view_bank_accounts"),
    ("R63.W07", "submit_topup_receipt"),
    ("R63.W08", "save_traveler_profile"),
    ("R63.W09", "select_service_and_form"),
    ("R63.W10", "fill_service_form"),
    ("R63.W11", "checkout_order_with_wallet"),
    ("R63.W12", "track_fulfillment_status"),
    ("R63.W13", "download_completion_document"),
]
for aid, step in web_journeys:
    add_row(aid, "R63", "Web", f"WebJourneyStep::{step}", "journey_step_complete", "packages/Rehla/Web/tests/Unit/CustomerWebJourneyTest.php", f"verifies customer journey step {step}")

# R63.A01-R63.A09: Admin Staff Journey Test Vectors
admin_journeys = [
    ("R63.A01", "admin_login_and_totp_mfa"),
    ("R63.A02", "review_pending_topups_queue"),
    ("R63.A03", "approve_valid_topup"),
    ("R63.A04", "reject_invalid_topup_with_reason"),
    ("R63.A05", "view_service_catalog_and_price_history"),
    ("R63.A06", "create_and_publish_form_version"),
    ("R63.A07", "inspect_executions_and_assign_staff"),
    ("R63.A08", "issue_customer_action_request"),
    ("R63.A09", "complete_execution_and_attach_document"),
]
for aid, step in admin_journeys:
    add_row(aid, "R63", "Admin", f"AdminJourneyStep::{step}", "admin_journey_step_complete", "packages/Rehla/Admin/tests/Unit/AdminStaffJourneyTest.php", f"verifies admin staff journey step {step}")

# R64: Dependency & Architecture Bounds
add_row("R64.01", "R64", "Core", "ArchitectureGuard", "forbidden_imports_rejected", "tests/Architecture/PackageDependencyTest.php", "ensures presentation packages are not imported by business domains")

# R65: Final Release Gate
add_row("R65.01", "R65", "Core", "ReleaseGate", "all_acceptance_tests_passing", "tests/Architecture/AcceptanceRegisterTest.php", "confirms all phase one requirements mapped and verified")

with open('docs/requirements/rehla-phase-1-acceptance.csv', 'w', newline='', encoding='utf-8') as f:
    writer = csv.DictWriter(f, fieldnames=[
        "acceptance_id", "source_requirement", "package", "interface", "db_invariant",
        "test_file", "test_name", "status", "evidence", "deferred_reason"
    ])
    writer.writeheader()
    for r in rows:
        writer.writerow(r)

print(f"Generated {len(rows)} acceptance register rows.")
