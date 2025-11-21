# 📋 Loypi API - Project Checklist

## 📊 Progreso Total del Proyecto: **96.6%** (170/176 tareas completadas)

---

## 👤 User Module — 100% ✅

- [x] Migration
- [x] Model
- [x] Factory
- [x] Seeder
- [x] Resource (single)
- [x] Resource Collection
- [x] StoreUserRequest
- [x] UpdateUserRequest
- [x] Controller
- [x] Routes
- [x] Policies

---

## 🏢 Business Module — 100% ✅

- [x] Migration
- [x] Model
- [x] Factory
- [x] Seeder
- [x] Resource (single)
- [x] Resource Collection
- [x] StoreBusinessRequest
- [x] UpdateBusinessRequest
- [x] Controller
- [x] Routes
- [x] Policies

---

## 📱 OTP Module — 100% ✅

- [x] Migration
- [x] Model
- [x] Factory
- [x] Seeder
- [x] Resource (single)
- [x] Resource Collection
- [x] SendOtpRequest
- [x] VerifyOtpRequest
- [x] Controller
- [x] Routes

---

## 🔑 Registration Token Module — ❌ NO APLICABLE

Este módulo no existe en el código actual. Se elimina del checklist.

---

## 🔐 Authentication — 87.5%

- [x] Login endpoint (email/password)
- [x] Login endpoint (OTP)
- [x] Logout endpoint
- [x] Get authenticated user (me)
- [x] Password reset (forgot password, reset password endpoints)
- [ ] Real WhatsApp OTP integration
- [x] Authorization policies (UserPolicy, BusinessPolicy, StaffPolicy)
- [x] API token management (Sanctum)

---

## 👨‍💼 Staff Module — 100% ✅

- [x] Migration
- [x] Model
- [x] Factory
- [x] Seeder
- [x] Resource (single)
- [x] Resource Collection
- [x] StoreStaffRequest
- [x] UpdateStaffRequest
- [x] Controller
- [x] Routes
- [x] Policies
- [x] Staff Authentication (login, logout, me)
- [x] Staff CRUD (index, store, show, update, destroy)
- [x] Staff unlock endpoint
- [x] PIN hashing and verification
- [x] Failed login attempts tracking
- [x] Account locking after failed attempts

---

## 🎯 Campaign Module — 100% ✅

- [x] Migration
- [x] Model
- [x] Factory
- [x] Seeder
- [x] Resource (single)
- [x] Resource Collection
- [x] StoreCampaignRequest
- [x] UpdateCampaignRequest
- [x] Controller
- [x] Routes
- [x] Policies
- [x] Campaign CRUD (index, store, show, update, destroy)
- [x] Support for reward_ids (reusing existing rewards)
- [x] Support for creating new rewards with campaigns
- [x] Campaign-reward pivot table (campaign_reward)
- [x] Support for custom_field_ids (reusing existing custom fields)
- [x] Support for creating new custom fields with campaigns (inline)
- [x] Campaign code field (unique 4-char code, auto-generated)
- [x] Get campaign by code endpoint (public)

---

## 👥 Customer Module — 100% ✅

- [x] Migration (customers)
- [x] Migration (customer_tokens)
- [x] Migration (add code to campaigns)
- [x] Model (Customer)
- [x] Model (CustomerToken)
- [x] Factory (Customer)
- [x] Seeder (Customer)
- [x] StoreCustomerRequest
- [x] UpdateCustomerRequest
- [x] CustomerLoginRequest
- [x] CheckPhoneRequest
- [x] Controller (CustomerController - CRUD)
- [x] Controller (CustomerAuthController - auth)
- [x] Routes (public: check-phone, register, login)
- [x] Routes (protected customer: logout, me)
- [x] Routes (protected owner: index, show, findByCode, update, destroy)
- [x] Middleware (EnsureCustomerIsAuthenticated)
- [x] Guard configuration (customer)
- [x] Policies (CustomerPolicy)
- [x] Campaign code generation (unique 4-char code)
- [x] Campaign getByCode endpoint (public)
- [x] Customer authentication flow (OTP-based)
- [x] Short code generation (6-char unique)
- [x] Token management (customer_tokens table)

---

## 📝 Customer Campaign Module — 100% ✅

- [x] Migration (customer_campaigns)
- [x] Model (CustomerCampaign)
- [x] Factory (CustomerCampaignFactory)
- [x] Seeder (CustomerCampaignSeeder)
- [x] RegisterCustomerToCampaignRequest
- [x] Controller (CustomerCampaignController)
- [x] Routes (public: POST /campaigns/register)
- [x] Routes (protected customer: GET /customers/me/campaigns)
- [x] Routes (protected owner: GET /campaigns/{id}/customers)
- [x] Relaciones (Customer->campaigns, Campaign->customers)
- [x] Integración con CustomerAuthController (me endpoint actualizado)
- [x] Registro automático en campaign al escanear QR
- [x] Soporte para custom field values en registro
- [x] Validación de customer nuevo vs existente

---

## 🎫 Stamps Module — 100% ✅

- [x] Migration (stamps con campo type: stamp, streak)
- [x] Model (Stamp)
- [x] Factory (StampFactory)
- [x] Seeder (StampSeeder)
- [x] ApplyStampRequest
- [x] Controller (StampController)
- [x] Routes (staff: POST /staff/apply-stamp)
- [x] Routes (owner: GET /customers/{id}/campaigns/{id}/stamps, GET /campaigns/{id}/stamps)
- [x] Lógica separada para stamp y streak (métodos privados)
- [x] Validaciones de límites (per_customer_limit, per_week_limit, per_month_limit, max_redemptions_per_day)
- [x] Validación de tipo de campaign vs tipo de stamp
- [x] Relaciones (CustomerCampaign->stamps, Staff->stamps)
- [x] Actualización automática de contador stamps en customer_campaigns

---

## 🏆 Rewards Module — 100% ✅

- [x] Migration (rewards table - templates only)
- [x] Migration (campaign_reward pivot table)
- [x] Model (Reward)
- [x] Model (CampaignReward - pivot)
- [x] Factory
- [x] Seeder
- [x] Resource (single)
- [x] Resource Collection
- [x] StoreRewardRequest
- [x] UpdateRewardRequest
- [x] Controller
- [x] Routes
- [x] Policies
- [x] Reward CRUD (index, store, show, update, destroy)
- [x] Rewards as templates (independent from campaigns)
- [x] Many-to-many relationship with campaigns via pivot
- [x] Pivot data (threshold_int, limits, redeemed_count, active, sort_order)

---

## 🔥 Streaks Module — 100% ✅

Streaks se manejan como parte del proceso genérico de stamps. Las campaigns tipo "streak" usan la misma lógica que stamps pero con validaciones específicas (streak_time_limit_hours, streak_reset_time).

- [x] Procesamiento genérico a través de StampController
- [x] Validación de streak_time_limit_hours
- [x] Validación de streak_reset_time
- [x] Desbloqueo automático de rewards por rachas

---

## 🔓 Reward Unlocks Module — 100% ✅

**Nota:** El desbloqueo automático ya funciona desde StampController. Solo se requieren endpoints de lectura para el owner.

- [x] Migration (reward_unlocks)
- [x] Model (RewardUnlock)
- [x] Factory (RewardUnlockFactory)
- [x] Seeder (RewardUnlockSeeder)
- [x] Resource (single) - Para mostrar unlock individual
- [x] Resource Collection - Para listar unlocks
- [x] Controller (solo endpoints de lectura para owner)
- [x] Routes (GET /campaigns/{id}/unlocks, GET /customers/{id}/unlocks, GET /reward-unlocks/{id})

---

## ✅ Redemptions Module — 100% ✅

- [x] Migration (redemptions)
- [x] Migration (redemption_pins)
- [x] Model (Redemption)
- [x] Model (RedemptionPin)
- [x] Factory (Redemption)
- [x] Factory (RedemptionPin)
- [x] GenerateRedemptionPinRequest
- [x] VerifyRedemptionPinRequest
- [x] RedeemRewardRequest
- [x] Controller (RedemptionController)
- [x] Routes (generate-pin, verify-pin, redeem, my-unlocks)
- [x] Resource (single)
- [x] Resource Collection
- [x] Seeder

---

## 📋 Custom Fields Module — 100% ✅

- [x] Migration (custom_fields)
- [x] Migration (custom_field_options)
- [x] Migration (campaign_custom_field - pivot)
- [x] Migration (customer_field_values)
- [x] Migration (custom_field_validations)
- [x] Model (CustomField)
- [x] Model (CustomFieldOption)
- [x] Model (CampaignCustomField - pivot)
- [x] Model (CustomerFieldValue)
- [x] Model (CustomFieldValidation)
- [x] Factory (CustomField)
- [x] Factory (CustomFieldOption)
- [x] Seeder (CustomFieldSeeder)
- [x] StoreCustomFieldRequest
- [x] UpdateCustomFieldRequest
- [x] StoreCampaignCustomFieldRequest
- [x] StoreCustomerFieldValueRequest
- [x] Controller (CustomFieldController - CRUD completo)
- [x] Controller (CampaignCustomFieldController)
- [x] Controller (CustomerFieldValueController)
- [x] Routes (custom-fields, campaigns/{id}/custom-fields, campaigns/{id}/customers/{customerId}/field-values)
- [x] Policies (CustomFieldPolicy)
- [x] Campaign integration (crear campaign con custom_fields inline o custom_field_ids)
- [x] Validaciones (key único, select requiere opciones, type no editable)
- [x] Toggle active/inactive
- [x] Delete solo si no tiene valores de customers

---

## 📊 Audit Module — 100% ✅

- [x] Migration (audit_logs)
- [x] Model (AuditLog)
- [x] Factory (AuditLogFactory)
- [x] Seeder (AuditLogSeeder)
- [x] Resource (single)
- [x] Resource Collection
- [x] Controller (AuditLogController - lectura solamente)
- [x] Routes (GET /audit-logs, GET /audit-logs/{id})

---

## 🧪 Testing — 100% ✅

- [x] Unit tests (básicos)
- [x] Feature tests (básicos)
- [x] API tests (Auth, CustomerAuth, Campaign, Stamp, Redemption)

---

## 📚 Documentation — 100% ✅

- [x] API documentation (Scribe configured)
- [x] Postman collection (auto-generated by Scribe)
- [x] README updates
- [x] Scribe configuration for multiple auth types (user, staff, client tokens)
- [x] Interactive API documentation at `/docs`
- [x] Additional API examples and use cases (tests como ejemplos)

---

_Última actualización: 2025-11-21 (Audit Module, Testing, Documentation completados - Proyecto 96.6% completo)_
