<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Person;
use App\Models\Credit;

class PersonCreditSeeder extends Seeder
{
    public function run(): void
    {
        // M1
        $this->seedCredits(1, [
            ['name' => 'Edward Norton',        'profile_url' => 'https://images.tmdb.org/t/p/w185/5XBzD5WuTyVQZeS4VI25z2moMeY.jpg', 'character' => 'The Narrator',         'order' => 0],
            ['name' => 'Brad Pitt',            'profile_url' => 'https://images.tmdb.org/t/p/w185/cckcYc2v0yh1tc9QjRelptcOBko.jpg',  'character' => 'Tyler Durden',          'order' => 1],
        ], [
            ['name' => 'David Fincher', 'profile_url' => 'https://images.tmdb.org/t/p/w185/tpEczFclQZuk81ATaw63meKLRxM.jpg', 'job' => 'Director',   'department' => 'Directing'],
        ]);

        // M2
        $this->seedCredits(2, [
            ['name' => 'Tanner Linsley',     'profile_url' => null, 'character' => 'Himself (Creator)',    'order' => 0],
            ['name' => 'Dominik Dorfmeister', 'profile_url' => null, 'character' => 'Himself (Co-creator)', 'order' => 1],
        ], [
            ['name' => 'Adam Wathan',    'profile_url' => null, 'job' => 'Director',  'department' => 'Directing'],
        ]);

        // M3
        $this->seedCredits(3, [
            ['name' => 'Brad Pitt',       'profile_url' => 'https://images.tmdb.org/t/p/w185/cckcYc2v0yh1tc9QjRelptcOBko.jpg',  'character' => 'Detective Mills',    'order' => 0],
            ['name' => 'Morgan Freeman',  'profile_url' => 'https://images.tmdb.org/t/p/w185/oIciMBZgqcFkKGMKsGZYNM3BL9z.jpg',  'character' => 'Detective Somerset', 'order' => 1],
        ], [
            ['name' => 'David Fincher',   'profile_url' => 'https://images.tmdb.org/t/p/w185/tpEczFclQZuk81ATaw63meKLRxM.jpg', 'job' => 'Director',      'department' => 'Directing'],
        ]);
        
        // M4
        $this->seedCredits(4, [
            ['name' => 'Choi Woo-shik',   'profile_url' => 'https://images.tmdb.org/t/p/w185/dN7hSHNGlbWJVtR0Pu5iIRrfWJk.jpg', 'character' => 'Ki-woo',      'order' => 0],
            ['name' => 'Park So-dam',     'profile_url' => 'https://images.tmdb.org/t/p/w185/n87GSMNPJc2MBmfKHjxjjGV6U3p.jpg', 'character' => 'Ki-jung',     'order' => 1],
        ], [
            ['name' => 'Bong Joon-ho',  'profile_url' => 'https://images.tmdb.org/t/p/w185/XzNTgOIKMV0lm0VVZB4aBcRqnk.jpg', 'job' => 'Director',   'department' => 'Directing'],
        ]);
        
        // M5 (The Lord of the Rings: ID 120)
        $this->seedCredits(120, [
            ['name' => 'Elijah Wood',   'profile_url' => 'https://images.tmdb.org/t/p/w185/7UKRbJBNG7mxBl2QQc5XsAh6F8B.jpg', 'character' => 'Frodo',      'order' => 0],
            ['name' => 'Ian McKellen',  'profile_url' => 'https://images.tmdb.org/t/p/w185/5np6cgAte21L27w70m7q0iQ5o69.jpg', 'character' => 'Gandalf',    'order' => 1],
        ], [
            ['name' => 'Peter Jackson',  'profile_url' => 'https://images.tmdb.org/t/p/w185/4c44N57Gv9f1jVIfg1F3X2IibYv.jpg', 'job' => 'Director',   'department' => 'Directing'],
        ]);
    }

    private function seedCredits(int $movieId, array $castData, array $crewData): void
    {
        foreach ($castData as $c) {
            $person = Person::firstOrCreate(
                ['name' => $c['name']],
                [
                    'profile_path'         => $c['profile_url'] ?? null,
                    'gender'               => isset($c['gender']) ? $c['gender'] : 2,
                    'known_for_department' => 'Acting',
                ]
            );

            Credit::create([
                'movie_id'       => $movieId,
                'person_id'      => $person->person_id,
                'credit_type'    => 'cast',
                'character_name' => $c['character'],
                'order'          => $c['order'],
            ]);
        }

        foreach ($crewData as $c) {
            $person = Person::firstOrCreate(
                ['name' => $c['name']],
                [
                    'profile_path'         => $c['profile_url'] ?? null,
                    'gender'               => 2,
                    'known_for_department' => $c['department'] ?? 'Directing',
                ]
            );

            Credit::create([
                'movie_id'    => $movieId,
                'person_id'   => $person->person_id,
                'credit_type' => 'crew',
                'job'         => $c['job'],
                'department'  => $c['department'],
            ]);
        }
    }
}
