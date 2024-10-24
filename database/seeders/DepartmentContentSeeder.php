<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DepartmentContentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $departmentContent = [
            [
                'alias' => 'cardiology',
                'title' => 'Кардіологія',
                'description' => 'Відділ, що спеціалізується на діагностиці та лікуванні серцево-судинних захворювань.',
            ],
            [
                'alias' => 'neurology',
                'title' => 'Неврологія',
                'description' => 'Відділ, що займається розладами центральної та периферичної нервової системи.',
            ],
            [
                'alias' => 'orthopedics',
                'title' => 'Ортопедія',
                'description' => 'Спеціалізується на лікуванні травм та захворювань кісток, суглобів і м’язів.',
            ],
            [
                'alias' => 'pediatrics',
                'title' => 'Педіатрія',
                'description' => 'Відділ для діагностики та лікування дітей і підлітків.',
            ],
            [
                'alias' => 'oncology',
                'title' => 'Онкологія',
                'description' => 'Спеціалізується на лікуванні ракових захворювань, включаючи хіміотерапію та радіотерапію.',
            ],
            [
                'alias' => 'gastroenterology',
                'title' => 'Гастроентерологія',
                'description' => 'Лікування захворювань шлунково-кишкового тракту, таких як виразка, гастрит, захворювання печінки',
            ],
            [
                'alias' => 'dermatology',
                'title' => 'Дерматологія',
                'description' => 'Лікування шкірних захворювань, включаючи алергії, інфекції та дерматити.',
            ],
            [
                'alias' => 'endocrinology',
                'title' => 'Ендокринологія',
                'description' => 'Спеціалізується на діагностиці та лікуванні гормональних порушень та захворювань ендокринної системи.',
            ],
            [
                'alias' => 'radiology',
                'title' => 'Радіологія',
                'description' => 'Відділ, що займається візуалізацією тіла пацієнтів за допомогою рентгену, МРТ, КТ для діагностики захворювань.',
            ],
            [
                'alias' => 'urology',
                'title' => 'Урологія',
                'description' => 'Лікування захворювань сечостатевої системи, таких як інфекції, камені в нирках та інші урологічні проблеми.',
            ],
        ];

        foreach ($departmentContent as $content) {
            $department = DB::table('department')->where('alias', $content['alias'])->first();

            if ($department) {
                DB::table('department_content')->insert([
                    'department_id' => $department->id,
                    'title' => $content['title'],
                    'description' => $content['description'],
                ]);
            }
        }
    }

}
