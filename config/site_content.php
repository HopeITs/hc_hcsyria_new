<?php

/*
 * Every picture, video and piece of copy on the public site that staff may change
 * from the dashboard (Site content). A page is a group of slots; a slot is one
 * thing on that page:
 *
 *   type     image | video | text | long (a paragraph)
 *   default  what the page shows until someone changes it: the file or words it always had
 *   box      images: the largest size kept, [width, height]; larger uploads are scaled down
 *   alpha    images: keep see-through parts (a PNG cut-out); otherwise stored as a JPEG
 *   where    one line telling staff where on the page it appears
 *
 * To make something else editable: add a slot here, then ask for it where the page
 * draws it, SiteContent::image('page.slot'), ::text() or ::video(). Nothing else.
 */

/** One programme card on the home page: a picture and a paragraph. */
$card = fn (string $name, string $title, string $image, string $text): array => [
    "{$name}_image" => ['type' => 'image', 'label' => "$title card picture", 'default' => "/images/home/$image", 'box' => [1200, 1600], 'where' => 'The picture on the programme card.'],
    "{$name}_text" => ['type' => 'long', 'label' => "$title card text", 'default' => $text, 'where' => 'The paragraph on the programme card.'],
];

return [
    /*
     * Pictures in the page that the visual editor does not offer to swap, by pattern on the address:
     * the ones staff already manage elsewhere (projects, news, successes, an initiative's own gallery,
     * roots of hope), icons, the torn-edge mask and our own uploads.
     */
    'not_swappable' => [
        '~^/images/(projects[a-z_]*|successes|news|rootsofhope|covers|site)/~i',
        '~^/images/initiatives/[^/]+/\d+/~i',
        '~^/images/ico/~i',
        '~image-mask~i',
    ],

    'pages' => [
        'home' => [
            'label' => 'Home',
            'url' => '/home',
            'icon' => 'home',
            'blurb' => 'The cover, the "why it matters" film and the eight programme cards.',
            'slots' => [
                'cover_desktop' => ['type' => 'image', 'label' => 'Cover photo, computers', 'default' => '/images/home/roots-cover-desktop.jpg', 'box' => [3000, 1600], 'where' => 'The full-width picture at the very top, on computers and tablets.'],
                'cover_mobile' => ['type' => 'image', 'label' => 'Cover photo, phones', 'default' => '/images/home/roots-cover-mobile.jpg', 'box' => [1080, 1920], 'where' => 'The tall picture at the very top, on phones.'],
                'magazine_desktop' => ['type' => 'image', 'label' => 'Magazine cover, computers', 'default' => '/images/home/roots-magazine-desktop.png', 'box' => [900, 1300], 'alpha' => true, 'where' => 'The magazine that sits on the cover photo and opens Roots of Hope.'],
                'magazine_mobile' => ['type' => 'image', 'label' => 'Magazine cover, phones', 'default' => '/images/home/roots-magazine-mobile.png', 'box' => [700, 1000], 'alpha' => true, 'where' => 'The same magazine, for phones.'],
                'matters_video' => ['type' => 'video', 'label' => '"Why it matters" film', 'default' => '/images/home/matters.mp4', 'where' => 'The silent looping film under the welcome line.'],
                ...$card('leap', 'LEAP', 'LEAP.jpg', 'The LEAP is designed to empower a new generation of young Syrian Christian leaders who live out their faith with integrity, insight, and empathy.Through a dynamic and values-driven approuch, that will help..'),
                ...$card('rehabilitation', 'Rehabilitation', 'Reh.jpg', 'Distorted financial capacities were followed by an earthquake that shook the infrastructure of the country, rehabilitating family residencies became the first step to re-normalizing life…'),
                ...$card('children_university', 'Children University', 'CU.jpg', 'Children were the youngest victims of war, which took a toll on their healthy upbringing, our child-friendly space is a lighthouse that promotes social-emotional health besides educational development…'),
                ...$card('school_to_school', 'School to School', 'S2S.jpg', 'The road to shaping the future generation of the country was paved with financial hardships jeopardizing the educational process, Polish schools helped schools in need with equipment and teachers training…'),
                ...$card('family_to_family', 'Family To Family', 'F2F.jpg', 'Families suffered to make ends meet amid a very sharp increase in the prices of daily commodities, which is why Polish families started supporting vulnerable families through monthly cash aid…'),
                ...$card('micro_project', 'Micro Project', 'MP.jpg', 'Family providers lost their livelihoods, becoming unable to generate an income, our program aimed to facilitate vocational recovery and encourage beneficiaries’ financial independence…'),
                ...$card('disc', 'DISC', 'DISC.jpg', 'Our training program focuses on equipping university graduates with job skills they lack sought by all employers and helping them land job opportunities to establish sustainable careers…'),
                ...$card('study_zone', 'Study Zone', 'SZ.jpg', "The scarcity of electricity disrupted university student's ability to complete their academic careers, what they truly needed, was a study-friendly environment equipped with basic amenities..."),
            ],
        ],
        'who-we-are' => [
            'label' => 'Who we are',
            'url' => '/who-we-are',
            'icon' => 'users',
            'blurb' => 'The mission section.',
            'slots' => [
                'mission_photo' => ['type' => 'image', 'label' => 'Mission photo', 'default' => '/images/mission/Responsibility.jpg', 'box' => [2000, 1400], 'where' => 'The photo beside the mission text.'],
            ],
        ],
        'news' => [
            'label' => 'Our news',
            'url' => '/our-news',
            'icon' => 'news',
            'blurb' => 'The newsletter page.',
            'slots' => [
                'header_image' => ['type' => 'image', 'label' => 'Header photo', 'default' => '/images/our-news/header-image.jpg', 'box' => [2400, 1200], 'where' => 'The banner at the top, used when no cover photo is set for the page.'],
                'magazine_image' => ['type' => 'image', 'label' => 'Magazine picture', 'default' => '/images/our-news/roots-of-hope-magazine.png', 'box' => [900, 1300], 'alpha' => true, 'where' => 'The Roots of Hope magazine beside the sign-up.'],
            ],
        ],
        'roots' => [
            'label' => 'Roots of Hope',
            'url' => '/rootsofhope',
            'icon' => 'book',
            'blurb' => 'The magazine page. The editions themselves are under Roots of Hope.',
            'slots' => [
                'banner' => ['type' => 'image', 'label' => 'Banner', 'default' => '/images/rootsofhope/Cover.png', 'box' => [3000, 1600], 'alpha' => true, 'where' => 'The picture across the top of the page.'],
            ],
        ],
        'study-zone' => [
            'label' => 'Study Zone',
            'url' => '/projects/study-zone',
            'icon' => 'pin',
            'blurb' => 'The three kinds of zone.',
            'slots' => [
                'social_image' => ['type' => 'image', 'label' => 'Social Zone picture', 'default' => '/images/home/zone-categories.jpg', 'box' => [1600, 1600], 'where' => 'Beside the Social Zone description.'],
                'quiet_image' => ['type' => 'image', 'label' => 'Quiet Zone picture', 'default' => '/images/home/zone-categories.jpg', 'box' => [1600, 1600], 'where' => 'Beside the Quiet Zone description.'],
                'silent_image' => ['type' => 'image', 'label' => 'Silent Zone picture', 'default' => '/images/home/zone-categories.jpg', 'box' => [1600, 1600], 'where' => 'Beside the Silent Zone description.'],
            ],
        ],
        'disc' => [
            'label' => 'DISC',
            'url' => '/projects/disc',
            'icon' => 'spark',
            'blurb' => 'The four course pictures.',
            'slots' => [
                'english_image' => ['type' => 'image', 'label' => 'English Skills picture', 'default' => '/images/initiatives/DISC/courses/english.jpg', 'box' => [1600, 1200], 'where' => 'Beside the English Skills course.'],
                'computer_image' => ['type' => 'image', 'label' => 'Computer Skills picture', 'default' => '/images/initiatives/DISC/courses/computer.jpg', 'box' => [1600, 1200], 'where' => 'Beside the Computer Skills course.'],
                'accompaniment_image' => ['type' => 'image', 'label' => 'Accompaniment picture', 'default' => '/images/initiatives/DISC/courses/compan.jpg', 'box' => [1600, 1200], 'where' => 'Beside the Accompaniment course.'],
                'employability_image' => ['type' => 'image', 'label' => 'Employability Skills picture', 'default' => '/images/initiatives/DISC/courses/skills.jpg', 'box' => [1600, 1200], 'where' => 'Beside the Employability Skills course.'],
            ],
        ],
        'leap' => [
            'label' => 'LEAP',
            'url' => '/projects/leap',
            'icon' => 'trophy',
            'blurb' => 'The steps picture.',
            'slots' => [
                'steps_image' => ['type' => 'image', 'label' => 'Steps picture', 'default' => '/images/home/steps.jpg', 'box' => [2400, 1200], 'where' => 'The wide picture near the foot of the page.'],
            ],
        ],
        'children-university' => ['label' => 'Children University', 'url' => '/projects/childrenuni', 'icon' => 'users', 'blurb' => 'The Children University page.', 'slots' => []],
        'school-to-school' => ['label' => 'School to School', 'url' => '/projects/school-to-school', 'icon' => 'book', 'blurb' => 'The School to School page.', 'slots' => []],
        'family-to-family' => ['label' => 'Family to Family', 'url' => '/projects/family-to-family', 'icon' => 'users', 'blurb' => 'The Family to Family page.', 'slots' => []],
        'christian-youth' => ['label' => 'Christian Youth', 'url' => '/christianyouth', 'icon' => 'spark', 'blurb' => 'The Syrian Christian Youth Leaders gathering page.', 'slots' => []],
        'micro' => [
            'label' => 'Micro projects',
            'url' => '/projects/micro-projects',
            'icon' => 'folder',
            'blurb' => 'The page members see before they sign in.',
            'slots' => [
                'locked_background' => ['type' => 'image', 'label' => 'Sign-in background', 'default' => '/images/micro/locked-projects-bg.png', 'box' => [2400, 1400], 'alpha' => true, 'where' => 'Behind the "members only" message.'],
            ],
        ],
    ],
];
