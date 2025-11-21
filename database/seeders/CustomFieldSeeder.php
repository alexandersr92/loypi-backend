<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\CustomField;
use App\Models\CustomFieldOption;
use Illuminate\Database\Seeder;

class CustomFieldSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $businesses = Business::all();

        foreach ($businesses as $business) {
            // Campo: Nombre completo (text, requerido)
            $fullNameField = CustomField::factory()
                ->for($business, 'business')
                ->text()
                ->required()
                ->create([
                    'key' => 'full_name',
                    'label' => 'Nombre Completo',
                    'description' => 'Ingresa tu nombre completo',
                    'type' => 'text',
                    'required' => true,
                    'extra' => [
                        'placeholder' => 'Ej: Juan Pérez',
                        'max_length' => 100,
                    ],
                ]);

            // Campo: Fecha de nacimiento (date, requerido)
            $birthDateField = CustomField::factory()
                ->for($business, 'business')
                ->date()
                ->required()
                ->create([
                    'key' => 'birth_date',
                    'label' => 'Fecha de Nacimiento',
                    'description' => 'Selecciona tu fecha de nacimiento',
                    'type' => 'date',
                    'required' => true,
                    'extra' => [
                        'min_date' => '1900-01-01',
                        'max_date' => 'now',
                    ],
                ]);

            // Campo: Teléfono (text, requerido)
            $phoneField = CustomField::factory()
                ->for($business, 'business')
                ->text()
                ->required()
                ->create([
                    'key' => 'phone',
                    'label' => 'Teléfono',
                    'description' => 'Ingresa tu número de teléfono',
                    'type' => 'text',
                    'required' => true,
                    'extra' => [
                        'placeholder' => '+52 123 456 7890',
                        'max_length' => 20,
                    ],
                ]);

            // Campo: Email (text, requerido)
            $emailField = CustomField::factory()
                ->for($business, 'business')
                ->text()
                ->required()
                ->create([
                    'key' => 'email',
                    'label' => 'Correo Electrónico',
                    'description' => 'Ingresa tu correo electrónico',
                    'type' => 'text',
                    'required' => true,
                    'extra' => [
                        'placeholder' => 'ejemplo@correo.com',
                        'max_length' => 255,
                    ],
                ]);

            // Campo: Edad (number, opcional)
            $ageField = CustomField::factory()
                ->for($business, 'business')
                ->number()
                ->create([
                    'key' => 'age',
                    'label' => 'Edad',
                    'description' => 'Ingresa tu edad',
                    'type' => 'number',
                    'required' => false,
                    'extra' => [
                        'min' => 13,
                        'max' => 120,
                        'step' => 1,
                    ],
                ]);

            // Campo: Género (select, opcional)
            $genderField = CustomField::factory()
                ->for($business, 'business')
                ->select()
                ->create([
                    'key' => 'gender',
                    'label' => 'Género',
                    'description' => 'Selecciona tu género',
                    'type' => 'select',
                    'required' => false,
                    'extra' => [
                        'multiple' => false,
                    ],
                ]);

            // Crear opciones para el campo de género
            $genderOptions = [
                ['value' => 'male', 'label' => 'Masculino', 'sort_order' => 0],
                ['value' => 'female', 'label' => 'Femenino', 'sort_order' => 1],
                ['value' => 'other', 'label' => 'Otro', 'sort_order' => 2],
                ['value' => 'prefer_not_to_say', 'label' => 'Prefiero no decir', 'sort_order' => 3],
            ];

            foreach ($genderOptions as $option) {
                CustomFieldOption::create([
                    'custom_field_id' => $genderField->id,
                    'value' => $option['value'],
                    'label' => $option['label'],
                    'sort_order' => $option['sort_order'],
                ]);
            }

            // Campo: Frecuencia de visita (select, opcional)
            $frequencyField = CustomField::factory()
                ->for($business, 'business')
                ->select()
                ->create([
                    'key' => 'visit_frequency',
                    'label' => 'Frecuencia de Visita',
                    'description' => '¿Con qué frecuencia nos visitas?',
                    'type' => 'select',
                    'required' => false,
                    'extra' => [
                        'multiple' => false,
                    ],
                ]);

            // Crear opciones para frecuencia de visita
            $frequencyOptions = [
                ['value' => 'daily', 'label' => 'Diario', 'sort_order' => 0],
                ['value' => 'weekly', 'label' => 'Semanal', 'sort_order' => 1],
                ['value' => 'monthly', 'label' => 'Mensual', 'sort_order' => 2],
                ['value' => 'occasionally', 'label' => 'Ocasionalmente', 'sort_order' => 3],
                ['value' => 'first_time', 'label' => 'Primera vez', 'sort_order' => 4],
            ];

            foreach ($frequencyOptions as $option) {
                CustomFieldOption::create([
                    'custom_field_id' => $frequencyField->id,
                    'value' => $option['value'],
                    'label' => $option['label'],
                    'sort_order' => $option['sort_order'],
                ]);
            }

            // Campo: Acepta términos y condiciones (boolean, requerido)
            $termsField = CustomField::factory()
                ->for($business, 'business')
                ->boolean()
                ->required()
                ->create([
                    'key' => 'accept_terms',
                    'label' => 'Acepto términos y condiciones',
                    'description' => 'Debes aceptar los términos y condiciones para continuar',
                    'type' => 'boolean',
                    'required' => true,
                    'extra' => [
                        'default' => false,
                    ],
                ]);

            // Campo: Código postal (text, opcional)
            $zipCodeField = CustomField::factory()
                ->for($business, 'business')
                ->text()
                ->create([
                    'key' => 'zip_code',
                    'label' => 'Código Postal',
                    'description' => 'Ingresa tu código postal',
                    'type' => 'text',
                    'required' => false,
                    'extra' => [
                        'placeholder' => '12345',
                        'max_length' => 10,
                    ],
                ]);

            // Campo: Ciudad (text, opcional)
            $cityField = CustomField::factory()
                ->for($business, 'business')
                ->text()
                ->create([
                    'key' => 'city',
                    'label' => 'Ciudad',
                    'description' => 'Ingresa tu ciudad',
                    'type' => 'text',
                    'required' => false,
                    'extra' => [
                        'placeholder' => 'Ciudad de México',
                        'max_length' => 100,
                    ],
                ]);
        }
    }
}
