<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class ServicesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $services = [
            // Кардіологія
            ['name' => 'Електрокардіограма (ЕКГ)', 'department_alias' => 'cardiology'],
            ['name' => 'УЗД серця (ехокардіографія)', 'department_alias' => 'cardiology'],
            ['name' => 'Консультація кардіолога', 'department_alias' => 'cardiology'],
            ['name' => 'Холтер-моніторинг', 'department_alias' => 'cardiology'],
            ['name' => 'Стрес-тестування', 'department_alias' => 'cardiology'],

            // Неврологія
            ['name' => 'Консультація невролога', 'department_alias' => 'neurology'],
            ['name' => 'МРТ головного мозку', 'department_alias' => 'neurology'],
            ['name' => 'Електроенцефалографія (ЕЕГ)', 'department_alias' => 'neurology'],
            ['name' => 'Транскраніальна доплерографія', 'department_alias' => 'neurology'],
            ['name' => 'Нейропсихологічне тестування', 'department_alias' => 'neurology'],

            // Ортопедія
            ['name' => 'Консультація ортопеда', 'department_alias' => 'orthopedics'],
            ['name' => 'Рентген суглобів', 'department_alias' => 'orthopedics'],
            ['name' => 'Лікування спортивних травм', 'department_alias' => 'orthopedics'],
            ['name' => 'Артроскопія', 'department_alias' => 'orthopedics'],
            ['name' => 'Лікування сколіозу', 'department_alias' => 'orthopedics'],

            // Педіатрія
            ['name' => 'Консультація педіатра', 'department_alias' => 'pediatrics'],
            ['name' => 'Вакцинація дітей', 'department_alias' => 'pediatrics'],
            ['name' => 'Огляд новонароджених', 'department_alias' => 'pediatrics'],
            ['name' => 'Консультація дитячого невролога', 'department_alias' => 'pediatrics'],
            ['name' => 'Лікування інфекційних захворювань у дітей', 'department_alias' => 'pediatrics'],

            // Онкологія
            ['name' => 'Консультація онколога', 'department_alias' => 'oncology'],
            ['name' => 'Хіміотерапія', 'department_alias' => 'oncology'],
            ['name' => 'Радіотерапія', 'department_alias' => 'oncology'],
            ['name' => 'Біопсія', 'department_alias' => 'oncology'],
            ['name' => 'Лікування пухлинних захворювань', 'department_alias' => 'oncology'],

            // Гастроентерологія
            ['name' => 'Консультація гастроентеролога', 'department_alias' => 'gastroenterology'],
            ['name' => 'УЗД органів черевної порожнини', 'department_alias' => 'gastroenterology'],
            ['name' => 'Гастроскопія', 'department_alias' => 'gastroenterology'],
            ['name' => 'Лікування виразкової хвороби', 'department_alias' => 'gastroenterology'],
            ['name' => 'Лікування захворювань печінки', 'department_alias' => 'gastroenterology'],

            // Дерматологія
            ['name' => 'Консультація дерматолога', 'department_alias' => 'dermatology'],
            ['name' => 'Лікування шкірних інфекцій', 'department_alias' => 'dermatology'],
            ['name' => 'Видалення папілом та родимок', 'department_alias' => 'dermatology'],
            ['name' => 'Дерматоскопія', 'department_alias' => 'dermatology'],
            ['name' => 'Лікування алергічних реакцій', 'department_alias' => 'dermatology'],

            // Ендокринологія
            ['name' => 'Консультація ендокринолога', 'department_alias' => 'endocrinology'],
            ['name' => 'Лікування цукрового діабету', 'department_alias' => 'endocrinology'],
            ['name' => 'УЗД щитоподібної залози', 'department_alias' => 'endocrinology'],
            ['name' => 'Гормональні дослідження', 'department_alias' => 'endocrinology'],
            ['name' => 'Лікування порушень обміну речовин', 'department_alias' => 'endocrinology'],

            // Радіологія
            ['name' => 'МРТ', 'department_alias' => 'radiology'],
            ['name' => 'Комп\'ютерна томографія (КТ)', 'department_alias' => 'radiology'],
            ['name' => 'Рентгенографія', 'department_alias' => 'radiology'],
            ['name' => 'Флюорографія', 'department_alias' => 'radiology'],
            ['name' => 'Мамографія', 'department_alias' => 'radiology'],

            // Урологія
            ['name' => 'Консультація уролога', 'department_alias' => 'urology'],
            ['name' => 'Лікування сечокам\'яної хвороби', 'department_alias' => 'urology'],
            ['name' => 'УЗД нирок', 'department_alias' => 'urology'],
            ['name' => 'Цистоскопія', 'department_alias' => 'urology'],
            ['name' => 'Лікування простатиту', 'department_alias' => 'urology'],
        ];

        foreach ($services as $service) {
            $department = DB::table('department')->where('alias', $service['department_alias'])->first();

            if ($department) {
                DB::table('services')->insert([
                    'name' => $service['name'],
                    'department_id' => $department->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

    }
}
