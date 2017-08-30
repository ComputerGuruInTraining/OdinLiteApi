<?php

use Illuminate\Database\Seeder;

class CaseNotesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
//        DB::table('case_notes')->insert([
//            'title' => 'Heard Suspicious Noise',
//            'img' => null,
//            'description' => 'There was a loud tapping sound at the back of the building which was investigated but the cause unknown.',
//            'mobile_user_id' => 1,
//            'shift_id' => 1
//        ]);

        factory(App\CaseNote::class, 10)->create();
    }
}
