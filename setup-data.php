<?php

function run_schema_file(PDO $pdo)
{
    $schema_path = __DIR__ . '/schema.sql';
    if (!is_file($schema_path)) {
        return ['Schema file not found.'];
    }

    $sql = file_get_contents($schema_path);
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    $messages = [];

    foreach ($statements as $statement) {
        $upper = strtoupper($statement);
        if (str_starts_with($upper, 'CREATE DATABASE') || str_starts_with($upper, 'USE ')) {
            continue;
        }

        $pdo->exec($statement);
    }

    $messages[] = 'Tables checked/created from schema.sql.';
    return $messages;
}

function seed_default_admin(PDO $pdo)
{
    $count = (int) $pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn();
    if ($count > 0) {
        return 'Admin seed skipped. Admin already exists.';
    }

    $stmt = $pdo->prepare(
        'INSERT INTO admins (name, email, password_hash)
         VALUES (:name, :email, :password_hash)'
    );
    $stmt->execute([
        ':name' => env_value('ADMIN_DEFAULT_NAME', 'Mutiur Rahman'),
        ':email' => env_value('ADMIN_DEFAULT_EMAIL', 'mutiur5bb@gmail.com'),
        ':password_hash' => password_hash(env_value('ADMIN_DEFAULT_PASSWORD', '12345678'), PASSWORD_DEFAULT),
    ]);

    return 'Admin seed inserted.';
}

function seed_events(PDO $pdo)
{
    $count = (int) $pdo->query('SELECT COUNT(*) FROM events')->fetchColumn();
    if ($count > 0) {
        return 'Event seed skipped. Events already exist.';
    }

    $stmt = $pdo->prepare(
        'INSERT INTO events (title, event_date, description, location, location_icon, sort_order)
         VALUES (:title, :event_date, :description, :location, :location_icon, :sort_order)'
    );
    $dates = [
        date('Y-m-d', strtotime('+15 days')),
        date('Y-m-d', strtotime('+5 days')),
        date('Y-m-d', strtotime('-10 days')),
    ];
    $events = [
        ['Algorithmic Mastery 2.0', $dates[0], 'A hands-on competitive programming session focused on dynamic programming patterns, interview practice, and timed problem sets.', 'Lab 701', 'location_on', 1],
        ['Bit2Byte Intra-Hackathon', $dates[1], 'A team-based build day where students prototype small but useful tools for campus workflows and present their work to mentors.', 'Main Auditorium', 'location_on', 2],
        ['Rust for Beginners', $dates[2], 'An introductory workshop covering ownership, memory safety, and practical examples for students exploring systems programming.', 'Session archive available', 'history', 3],
    ];

    foreach ($events as $event) {
        $stmt->execute([
            ':title' => $event[0],
            ':event_date' => $event[1],
            ':description' => $event[2],
            ':location' => $event[3],
            ':location_icon' => $event[4],
            ':sort_order' => $event[5],
        ]);
    }

    return 'Event seed inserted.';
}

function seed_projects(PDO $pdo)
{
    $count = (int) $pdo->query('SELECT COUNT(*) FROM projects')->fetchColumn();
    if ($count > 0) {
        return 'Project seed skipped. Projects already exist.';
    }

    $stmt = $pdo->prepare(
        'INSERT INTO projects (title, description, tags, sort_order)
         VALUES (:title, :description, :tags, :sort_order)'
    );

    $projects = [
        ['Campus Resource Portal', 'A central place for club notes, workshop material, event resources, and onboarding guides for new members.', 'HTML, CSS, JavaScript', 1],
        ['Event Registration System', 'A lightweight registration and attendee tracking tool for club workshops, competitions, and internal training programs.', 'PHP, JSON, Forms', 2],
    ];

    foreach ($projects as $project) {
        $stmt->execute([
            ':title' => $project[0],
            ':description' => $project[1],
            ':tags' => $project[2],
            ':sort_order' => $project[3],
        ]);
    }

    return 'Project seed inserted.';
}

function member_batch_from_roll($roll)
{
    return '2k' . substr((string) $roll, 0, 2);
}

function seed_members(PDO $pdo)
{
    $stmt = $pdo->prepare(
        'INSERT INTO members
            (full_name, email, phone, student_id, department, batch, photo_path, skills, reason_for_joining, status)
         VALUES
            (:full_name, :email, NULL, :student_id, :department, :batch, NULL, NULL, NULL, :status)'
    );
    $exists = $pdo->prepare('SELECT COUNT(*) FROM members WHERE student_id = :student_id');

    $members = [
        ['2107004', 'Tawhidul Hasan'],
        ['2107006', 'Sarwad Hasan Siddiqui'],
        ['2107044', 'Arafat Islam'],
        ['2107055', 'Arka Braja Prasad Nath'],
        ['2107063', 'MD Rahul Sheikh'],
        ['2107066', 'Al Shariar Hossain'],
        ['2207005', 'Shahriar Hossain Prottoy'],
        ['2207008', 'Kazi Sakibul Hasan'],
        ['2207009', 'Md Istiaque Ahmed Asif'],
        ['2207011', 'Md Sulaiman'],
        ['2207020', 'Adib'],
        ['2207026', 'Sazzad Ahmed'],
        ['2207027', 'Utsa Roy'],
        ['2207030', 'Progga Paromita'],
        ['2207033', 'Anwesha Das Sreya'],
        ['2207034', 'Fariha Tabassum'],
        ['2207041', 'MD. Shomik Shahriar'],
        ['2207057', 'Shohana Akter Rabina'],
        ['2207063', 'Abida Alam Riti'],
        ['2207070', 'Fatihatun Nazat'],
        ['2207076', 'Jannatul Eusra'],
        ['2207080', 'Md. Farhaduzzaman Rume'],
        ['2207081', 'Naurina Haque'],
        ['2207097', 'Mutiur Rahman'],
        ['2207100', 'Bikon Ghosh'],
        ['2207110', 'Tamal Ghosh'],
        ['2207116', 'Ashrafur Rahman Nihad'],
        ['2207117', 'Allfi Sharin'],
        ['2207118', 'Dadhichi Sarker Shayon'],
        ['2207119', 'Fatema Tuj Zohra'],
        ['2307003', 'Samun Sadab Wafi'],
        ['2307036', 'Mujahid Hossen Sagar'],
        ['2307051', 'Md. Jakaria Omi'],
        ['2307053', 'Miah Tahsin Ibna Mezan'],
        ['2307068', 'Lailatun Nesa (Lamisa)'],
        ['2307074', 'Md. Abdullahil Kafi'],
        ['2307079', 'Shrayashee Saha'],
        ['2307089', 'Rahanuma Rashid'],
        ['2307095', 'Jahed Ahmed'],
    ];

    $inserted = 0;
    foreach ($members as $member) {
        $exists->execute([':student_id' => $member[0]]);
        if ((int) $exists->fetchColumn() > 0) {
            continue;
        }

        $stmt->execute([
            ':full_name' => $member[1],
            ':email' => $member[0] . '@stud.kuet.ac.bd',
            ':student_id' => $member[0],
            ':department' => 'Computer Science and Engineering',
            ':batch' => member_batch_from_roll($member[0]),
            ':status' => 'approved',
        ]);
        $inserted++;
    }
    $pdo->prepare('UPDATE members SET photo_path = CONCAT(?, ".jpg"), status = "pending" WHERE student_id = ?')->execute(['uploads/members/2207097', '2207097']);


    return "Member seed inserted {$inserted} new records.";
}

function seed_committee(PDO $pdo)
{
    $count = (int) $pdo->query('SELECT COUNT(*) FROM committee')->fetchColumn();
    if ($count > 0) {
        return 'Committee seed skipped. Committee members already exist.';
    }

    $stmt = $pdo->prepare(
        'INSERT INTO committee (name, role, photo_path, sort_order)
         VALUES (:name, :role, :photo_path, :sort_order)'
    );

    $members = [
        ['Md. Faysal Mahmud', 'President', '', 1],
        ['Ahmed Nur E Safa', 'General Secretary', '', 2],
        ['S M. Sadikuzzaman Abir', 'Vice-President', '', 3],
        ['Raufun Ahsan', 'Vice-President', '', 4],
        ['Md. Sakibur Rahman', 'Assistant General Secretary', '', 5],
        ['Md. Iqbal Mahmud Moon', 'Assistant General Secretary', '', 6],
        ['Kazi Tasrif', 'Joint Secretary', '', 7],
        ['Md. Minhaz Mahmud Mahadi', 'Joint Secretary', '', 8],
        ['Md. Tahsinur Rahman', 'Treasurer', '', 9],
        ['Md. Mofazzal Hosen', 'Assistant Treasurer', '', 10],
        ['Sourav Debnath', 'Assistant Treasurer', '', 11],
        ['Mohammad Abir Rahman', 'Workshop Manager', '', 12],
        ['Prova Rani Paul', 'Workshop Manager', '', 13],
        ['Efty Hasan', 'Assistant Workshop Manager', '', 14],
        ['Anirban Ghosh Argha', 'Assistant Workshop Manager', '', 15],
        ['Al Nahian Zarif', 'Organizing Secretary', '', 16],
        ['Md.Nayeem', 'Assistant Organizing Secretary', '', 17],
        ['Farhan Tahmid', 'Assistant Organizing Secretary', '', 18],
        ['Md. Shifat Hasan', 'Hackathon manager', '', 19],
        ['Anika Nawar', 'Hackathon manager', '', 20],
        ['Ariful Alam Mahim', 'Technical Writer', '', 21],
        ['Ankon Roy', 'Technical Writer', '', 22],
        ['Dip Shekhor Datta', 'Technical Writer', '', 23],
        ['Adiba Tahsin', 'Technical Writer', '', 24],
        ['Md Mubin Islam Alif', 'Technical Writer', '', 25],
        ['Arpita Das', 'Technical Writer', '', 26],
        ['Md. Kawsar MAhmud Khan Zunayed', 'Senior Mentor For Boys', '', 27],
        ['Tanzir Mannan Turzo', 'Senior Mentor For Boys', '', 28],
        ['Mayesha Marzia Zaman', 'Senior Mentor For Girls', '', 29],
        ['Sumaiya Khan', 'Senior Mentor For Girls', '', 30],
        ['Samioul Rian', 'Design Manager', '', 31],
        ['Rafsan Jani', 'Design Manager', '', 32],
        ['Abdullah Al Saif', 'Junior Mentor For Boys', '', 33],
        ['Sheikh Mohammad Galib', 'Junior Mentor For Boys', '', 34],
        ['Tasmir Hossain Zihad', 'Junior Mentor For Boys', '', 35],
        ['H.M. Azrof', 'Junior Mentor For Boys', '', 36],
        ['Sadia Mostofa', 'Junior Mentor For Girls', '', 37],
        ['Tajnoor Sultana', 'Junior Mentor For Girls', '', 38],
        ['Naveed Lihazi', 'Senior Member', '', 39],
        ['Robiul Islam Ryad', 'Senior Member', '', 40],
        ['Md. Tanvir', 'Senior Member', '', 41],
        ['Hanium Maria Joli', 'Senior Member', '', 42],
        ['Syeda Hafsa Tazrian', 'Senior Member', '', 43],
        ['Md. Babla Islam', 'Senior Member', '', 44],
        ['Md. Mahamudul Islam Shawcha', 'Senior Member', '', 45],
        ['Mst Sabekunnahar Naboni', 'Senior Member', '', 46],
        ['Mosaddek Ali Shishir', 'Executive Member', '', 47],
        ['Rafsani Shazid', 'Executive Member', '', 48],
        ['Eftakar Jaman Arfan', 'Executive Member', '', 49],
        ['Shakhoyat Rahman Shujon', 'Executive Member', '', 50],
        ['Al Mubtasim Preom', 'Executive Member', '', 51],
        ['Shoaib Hasan Niloy', 'Executive Member', '', 52],
        ['Zunaied Nudar', 'Executive Member', '', 53],
        ['Kazi Rifat Al Muin', 'Executive Member', '', 54],
        ['Khadimul Mahi', 'Executive Member', '', 55],
        ['Ariyan Aftab Spandan', 'Executive Member', '', 56],
        ['Jahid Hasan Jim', 'Executive Member', '', 57],
        ['Jahid Hasan', 'Executive Member', '', 58],
        ['Rezwan Ahammad Raad', 'Executive Member', '', 59],
        ['Rubayet Nabil', 'Executive Member', '', 60],
        ['Salehin Uddin Sakin', 'Executive Member', '', 61],
        ['Khalid Ahammed', 'Batch Representative(2k22)', '', 62],
        ['Issac Anik Sarkar', 'Batch Representative(2k22)', '', 63],
        ['Sanjidur Rahman', 'Batch Representative(2k23)', '', 64],
        ['Souvik Kundu', 'Batch Representative(2k23)', '', 65],
        ['Md Fahim Hossen', 'Associative Member', '', 66],
        ['Sanzida Alam Jerin', 'Associative Member', '', 67],
        ['Md Khorshed Sheikh', 'Associative Member', '', 68],
        ['Zaina Rahman', 'Associative Member', '', 69],
        ['Saleh Sadid Mir', 'Associative Member', '', 70],
        ['Suhita Islam Aurthi', 'Associative Member', '', 71],
        ['Abir Hasan Arko', 'Associative Member', '', 72],
        ['Mukta Rani Baishnob', 'Associative Member', '', 73],
        ['Nurul Absar Shadik', 'Associative Member', '', 74],
        ['Mirza Samia', 'Associative Member', '', 75],
        ['Mutiur Rahman', 'Associative Member', '', 76],
        ['Plabon Barua', 'Associative Member', '', 77],
        ['Aurin Farzana', 'Associative Member', '', 78],
        ['Tahmid Hossain Chowdhury Mahin', 'Associative Member', '', 79],
        ['Munem Shahriar Nijhum', 'Associative Member', '', 80],
        ['Suaib Ahmed Safi', 'Associative Member', '', 81],
        ['Hirobi Chakma', 'Associative Member', '', 82],
    ];

    foreach ($members as $member) {
        $stmt->execute([
            ':name' => $member[0],
            ':role' => $member[1],
            ':photo_path' => $member[2],
            ':sort_order' => $member[3],
        ]);
    }

    return 'Committee seed inserted.';
}

function seed_all_data(PDO $pdo)
{
    return [
        seed_default_admin($pdo),
        seed_members($pdo),
        seed_events($pdo),
        seed_projects($pdo),
        seed_committee($pdo),
    ];
}


