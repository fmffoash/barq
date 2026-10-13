<?php

namespace App\Support;

// مكتبة رسومات الـ3D (2026-10-11، فؤاد: "كل فئة حسب اللي تحتها رسمة مناسبة" + "الرسومات متاحة أستخدمها
// أو أعمل ميكس بينها وبين قوالب تانية"). كل رسمة هنا وصف بيانات (قطع: box/cyl/ball/shape/glyph/plane/group)
// بيترندر بـ site/partials/depth/scenes/_spec. الإحداثيات بالـrem من نص الرسمة (y لتحت). الألوان رموز
// (color()) فبتتغيّر مع ألوان الموقع. الـ20 رسمة الأولى ملفات Blade لوحدها (ORIGINAL) — كلهم مع بعض = all().
final class SceneLibrary
{
    // رسومات الدفعة الأولى (ملف Blade لكل واحدة في site/partials/depth/scenes).
    public const ORIGINAL = [
        'brew' => 'فنجان قهوة', 'helix' => 'شريط DNA', 'gloss' => 'زجاجات تجميل', 'forge' => 'دمبل',
        'skyline' => 'أبراج', 'parcel' => 'صناديق شحن', 'scholar' => 'كتاب وكاب تخرج', 'verdict' => 'ميزان',
        'blocks' => 'برج طوب', 'torque' => 'عجلة وترس', 'festive' => 'هدية وبلالين', 'globe' => 'كرة أرضية',
        'cubic' => 'مكعب كود', 'canvas' => 'لوحات فنية', 'atelier' => 'فستان على شماعة', 'bakery' => 'تورتة',
        'nursery' => 'مكعبات لعب', 'shutter' => 'كاميرا', 'essence' => 'زجاجة عطر', 'pawprint' => 'بصمة وعضمة',
    ];

    // (2026-10-13) رسومات من صور 3D جاهزة (Microsoft Fluent 3D Emoji، MIT — public/images/scenes3d) بتغلب
    // الرسم بالكود لنفس المفتاح. main = الصورة الكبيرة، satellites = [ملف، مقاس rem، left، top، عمق 0..1].
    public const IMAGE_SCENES = [
        // «Wheel» عند مايكروسوفت عجلة خشب (مش كاوتش) — الكاوتش والصيانة العربية هي الأساس.
        'brew' => ['main' => 'hot_beverage_3d.png', 'satellites' => [['croissant_3d.png', 5.4, '-2%', '8%', 0.2], ['shortcake_3d.png', 4.4, '74%', '4%', 0.5], ['sparkles_3d.png', 3.4, '78%', '64%', 0.85]]],
        'helix' => ['main' => 'dna_3d.png', 'satellites' => [['microscope_3d.png', 5.2, '-4%', '58%', 0.15], ['stethoscope_3d.png', 4.4, '74%', '6%', 0.6], ['sparkles_3d.png', 3, '6%', '4%', 0.9]]],
        'gloss' => ['main' => 'lipstick_3d.png', 'satellites' => [['lotion_bottle_3d.png', 5.2, '72%', '60%', 0.2], ['mirror_3d.png', 4.4, '-2%', '6%', 0.55], ['sparkles_3d.png', 3.2, '80%', '2%', 0.85]]],
        'forge' => ['main' => 'running_shoe_3d.png', 'satellites' => [['stopwatch_3d.png', 5.4, '-2%', '8%', 0.2], ['trophy_3d.png', 4.4, '74%', '4%', 0.5], ['sports_medal_3d.png', 3.4, '78%', '64%', 0.85]]],
        'skyline' => ['main' => 'cityscape_3d.png', 'satellites' => [['office_building_3d.png', 5.2, '-4%', '58%', 0.15], ['key_3d.png', 4.4, '74%', '6%', 0.6], ['sparkles_3d.png', 3, '6%', '4%', 0.9]]],
        'parcel' => ['main' => 'package_3d.png', 'satellites' => [['shopping_bags_3d.png', 5.2, '72%', '60%', 0.2], ['shopping_cart_3d.png', 4.4, '-2%', '6%', 0.55], ['delivery_truck_3d.png', 3.2, '80%', '2%', 0.85]]],
        'scholar' => ['main' => 'graduation_cap_3d.png', 'satellites' => [['books_3d.png', 5.4, '-2%', '8%', 0.2], ['pencil_3d.png', 4.4, '74%', '4%', 0.5], ['light_bulb_3d.png', 3.4, '78%', '64%', 0.85]]],
        'verdict' => ['main' => 'balance_scale_3d.png', 'satellites' => [['scroll_3d.png', 5.2, '-4%', '58%', 0.15], ['classical_building_3d.png', 4.4, '74%', '6%', 0.6], ['briefcase_3d.png', 3, '6%', '4%', 0.9]]],
        'blocks' => ['main' => 'building_construction_3d.png', 'satellites' => [['brick_3d.png', 5.2, '72%', '60%', 0.2], ['hammer_3d.png', 4.4, '-2%', '6%', 0.55], ['toolbox_3d.png', 3.2, '80%', '2%', 0.85]]],
        'torque' => ['main' => 'automobile_3d.png', 'satellites' => [['wrench_3d.png', 5.4, '-2%', '8%', 0.2], ['gear_3d.png', 4.4, '74%', '4%', 0.5], ['oil_drum_3d.png', 3.4, '78%', '64%', 0.85]]],
        'festive' => ['main' => 'wrapped_gift_3d.png', 'satellites' => [['balloon_3d.png', 5.2, '-4%', '58%', 0.15], ['party_popper_3d.png', 4.4, '74%', '6%', 0.6], ['confetti_ball_3d.png', 3, '6%', '4%', 0.9]]],
        'globe' => ['main' => 'globe_showing_europe_africa_3d.png', 'satellites' => [['airplane_3d.png', 5.2, '72%', '60%', 0.2], ['luggage_3d.png', 4.4, '-2%', '6%', 0.55], ['compass_3d.png', 3.2, '80%', '2%', 0.85]]],
        'cubic' => ['main' => 'laptop_3d.png', 'satellites' => [['gear_3d.png', 5.4, '-2%', '8%', 0.2], ['rocket_3d.png', 4.4, '74%', '4%', 0.5], ['light_bulb_3d.png', 3.4, '78%', '64%', 0.85]]],
        'canvas' => ['main' => 'artist_palette_3d.png', 'satellites' => [['framed_picture_3d.png', 5.2, '-4%', '58%', 0.15], ['paintbrush_3d.png', 4.4, '74%', '6%', 0.6], ['sparkles_3d.png', 3, '6%', '4%', 0.9]]],
        'atelier' => ['main' => 'dress_3d.png', 'satellites' => [['handbag_3d.png', 5.2, '72%', '60%', 0.2], ['high_heeled_shoe_3d.png', 4.4, '-2%', '6%', 0.55], ['ribbon_3d.png', 3.2, '80%', '2%', 0.85]]],
        'bakery' => ['main' => 'birthday_cake_3d.png', 'satellites' => [['cupcake_3d.png', 5.4, '-2%', '8%', 0.2], ['candy_3d.png', 4.4, '74%', '4%', 0.5], ['sparkles_3d.png', 3.4, '78%', '64%', 0.85]]],
        'nursery' => ['main' => 'teddy_bear_3d.png', 'satellites' => [['baby_bottle_3d.png', 5.2, '-4%', '58%', 0.15], ['balloon_3d.png', 4.4, '74%', '6%', 0.6], ['yo_yo_3d.png', 3, '6%', '4%', 0.9]]],
        'shutter' => ['main' => 'camera_with_flash_3d.png', 'satellites' => [['film_frames_3d.png', 5.2, '72%', '60%', 0.2], ['framed_picture_3d.png', 4.4, '-2%', '6%', 0.55], ['sparkles_3d.png', 3.2, '80%', '2%', 0.85]]],
        'essence' => ['main' => 'lotion_bottle_3d.png', 'satellites' => [['rose_3d.png', 5.4, '-2%', '8%', 0.2], ['ribbon_3d.png', 4.4, '74%', '4%', 0.5], ['sparkles_3d.png', 3.4, '78%', '64%', 0.85]]],
        'pawprint' => ['main' => 'paw_prints_3d.png', 'satellites' => [['dog_face_3d.png', 5.2, '-4%', '58%', 0.15], ['cat_face_3d.png', 4.4, '74%', '6%', 0.6], ['bone_3d.png', 3, '6%', '4%', 0.9]]],
        'dish' => ['main' => 'fork_and_knife_with_plate_3d.png', 'satellites' => [['green_salad_3d.png', 5.2, '72%', '60%', 0.2], ['cooking_3d.png', 4.4, '-2%', '6%', 0.55], ['sparkles_3d.png', 3.2, '80%', '2%', 0.85]]],
        'beanpot' => ['main' => 'pot_of_food_3d.png', 'satellites' => [['falafel_3d.png', 5.4, '-2%', '8%', 0.2], ['beans_3d.png', 4.4, '74%', '4%', 0.5], ['flatbread_3d.png', 3.4, '78%', '64%', 0.85]]],
        'pizza' => ['main' => 'pizza_3d.png', 'satellites' => [['hot_pepper_3d.png', 5.2, '-4%', '58%', 0.15], ['tomato_3d.png', 4.4, '74%', '6%', 0.6], ['cheese_wedge_3d.png', 3, '6%', '4%', 0.9]]],
        'skewer' => ['main' => 'meat_on_bone_3d.png', 'satellites' => [['fire_3d.png', 5.2, '72%', '60%', 0.2], ['hot_pepper_3d.png', 4.4, '-2%', '6%', 0.55], ['onion_3d.png', 3.2, '80%', '2%', 0.85]]],
        'juice' => ['main' => 'tropical_drink_3d.png', 'satellites' => [['tangerine_3d.png', 5.4, '-2%', '8%', 0.2], ['lemon_3d.png', 4.4, '74%', '4%', 0.5], ['strawberry_3d.png', 3.4, '78%', '64%', 0.85]]],
        'tooth' => ['main' => 'tooth_3d.png', 'satellites' => [['toothbrush_3d.png', 5.2, '-4%', '58%', 0.15], ['sparkles_3d.png', 4.4, '74%', '6%', 0.6], ['sparkles_3d.png', 3, '6%', '4%', 0.9]]],
        'stetho' => ['main' => 'anatomical_heart_3d.png', 'satellites' => [['stethoscope_3d.png', 5.2, '72%', '60%', 0.2], ['pill_3d.png', 4.4, '-2%', '6%', 0.55], ['thermometer_3d.png', 3.2, '80%', '2%', 0.85]]],
        'joint' => ['main' => 'bone_3d.png', 'satellites' => [['crutch_3d.png', 5.4, '-2%', '8%', 0.2], ['adhesive_bandage_3d.png', 4.4, '74%', '4%', 0.5], ['x_ray_3d.png', 3.4, '78%', '64%', 0.85]]],
        'eye' => ['main' => 'eye_3d.png', 'satellites' => [['glasses_3d.png', 5.2, '-4%', '58%', 0.15], ['magnifying_glass_tilted_left_3d.png', 4.4, '74%', '6%', 0.6], ['sparkles_3d.png', 3, '6%', '4%', 0.9]]],
        'teddy' => ['main' => 'teddy_bear_3d.png', 'satellites' => [['stethoscope_3d.png', 5.2, '72%', '60%', 0.2], ['baby_bottle_3d.png', 4.4, '-2%', '6%', 0.55], ['balloon_3d.png', 3.2, '80%', '2%', 0.85]]],
        'capsule' => ['main' => 'pill_3d.png', 'satellites' => [['syringe_3d.png', 5.4, '-2%', '8%', 0.2], ['adhesive_bandage_3d.png', 4.4, '74%', '4%', 0.5], ['medical_symbol_3d.png', 3.4, '78%', '64%', 0.85]]],
        'tubes' => ['main' => 'test_tube_3d.png', 'satellites' => [['microscope_3d.png', 5.2, '-4%', '58%', 0.15], ['petri_dish_3d.png', 4.4, '74%', '6%', 0.6], ['dna_3d.png', 3, '6%', '4%', 0.9]]],
        'drop' => ['main' => 'droplet_3d.png', 'satellites' => [['lotion_bottle_3d.png', 5.2, '72%', '60%', 0.2], ['sponge_3d.png', 4.4, '-2%', '6%', 0.55], ['sparkles_3d.png', 3.2, '80%', '2%', 0.85]]],
        'barber' => ['main' => 'barber_pole_3d.png', 'satellites' => [['razor_3d.png', 5.4, '-2%', '8%', 0.2], ['scissors_3d.png', 4.4, '74%', '4%', 0.5], ['hair_pick_3d.png', 3.4, '78%', '64%', 0.85]]],
        'polish' => ['main' => 'gem_stone_3d.png', 'satellites' => [['ring_3d.png', 5.2, '-4%', '58%', 0.15], ['ribbon_3d.png', 4.4, '74%', '6%', 0.6], ['sparkles_3d.png', 3, '6%', '4%', 0.9]]],
        'stones' => ['main' => 'candle_3d.png', 'satellites' => [['rock_3d.png', 5.2, '72%', '60%', 0.2], ['herb_3d.png', 4.4, '-2%', '6%', 0.55], ['soap_3d.png', 3.2, '80%', '2%', 0.85]]],
        'lipstick' => ['main' => 'lipstick_3d.png', 'satellites' => [['mirror_3d.png', 5.4, '-2%', '8%', 0.2], ['kiss_mark_3d.png', 4.4, '74%', '4%', 0.5], ['sparkles_3d.png', 3.4, '78%', '64%', 0.85]]],
        'yoga' => ['main' => 'ballet_shoes_3d.png', 'satellites' => [['cup_with_straw_3d.png', 5.2, '-4%', '58%', 0.15], ['sports_medal_3d.png', 4.4, '74%', '6%', 0.6], ['sparkles_3d.png', 3, '6%', '4%', 0.9]]],
        'boxing' => ['main' => 'boxing_glove_3d.png', 'satellites' => [['martial_arts_uniform_3d.png', 5.2, '72%', '60%', 0.2], ['trophy_3d.png', 4.4, '-2%', '6%', 0.55], ['collision_3d.png', 3.2, '80%', '2%', 0.85]]],
        'mat' => ['main' => 'lotus_3d.png', 'satellites' => [['herb_3d.png', 5.4, '-2%', '8%', 0.2], ['candle_3d.png', 4.4, '74%', '4%', 0.5], ['sparkles_3d.png', 3.4, '78%', '64%', 0.85]]],
        'swim' => ['main' => 'ring_buoy_3d.png', 'satellites' => [['water_wave_3d.png', 5.2, '-4%', '58%', 0.15], ['one_piece_swimsuit_3d.png', 4.4, '74%', '6%', 0.6], ['goggles_3d.png', 3, '6%', '4%', 0.9]]],
        'villa' => ['main' => 'house_with_garden_3d.png', 'satellites' => [['palm_tree_3d.png', 5.2, '72%', '60%', 0.2], ['key_3d.png', 4.4, '-2%', '6%', 0.55], ['sparkles_3d.png', 3.2, '80%', '2%', 0.85]]],
        'chalet' => ['main' => 'beach_with_umbrella_3d.png', 'satellites' => [['hut_3d.png', 5.4, '-2%', '8%', 0.2], ['palm_tree_3d.png', 4.4, '74%', '4%', 0.5], ['sun_3d.png', 3.4, '78%', '64%', 0.85]]],
        'storefront' => ['main' => 'convenience_store_3d.png', 'satellites' => [['shopping_bags_3d.png', 5.2, '-4%', '58%', 0.15], ['label_3d.png', 4.4, '74%', '6%', 0.6], ['money_bag_3d.png', 3, '6%', '4%', 0.9]]],
        'key' => ['main' => 'key_3d.png', 'satellites' => [['house_3d.png', 5.2, '72%', '60%', 0.2], ['old_key_3d.png', 4.4, '-2%', '6%', 0.55], ['sparkles_3d.png', 3.2, '80%', '2%', 0.85]]],
        'dairy' => ['main' => 'glass_of_milk_3d.png', 'satellites' => [['cheese_wedge_3d.png', 5.4, '-2%', '8%', 0.2], ['butter_3d.png', 4.4, '74%', '4%', 0.5], ['cow_face_3d.png', 3.4, '78%', '64%', 0.85]]],
        'spice' => ['main' => 'jar_3d.png', 'satellites' => [['salt_3d.png', 5.2, '-4%', '58%', 0.15], ['garlic_3d.png', 4.4, '74%', '6%', 0.6], ['hot_pepper_3d.png', 3, '6%', '4%', 0.9]]],
        'cart' => ['main' => 'shopping_cart_3d.png', 'satellites' => [['bread_3d.png', 5.2, '72%', '60%', 0.2], ['red_apple_3d.png', 4.4, '-2%', '6%', 0.55], ['carrot_3d.png', 3.2, '80%', '2%', 0.85]]],
        'phone' => ['main' => 'mobile_phone_3d.png', 'satellites' => [['headphone_3d.png', 5.4, '-2%', '8%', 0.2], ['battery_3d.png', 4.4, '74%', '4%', 0.5], ['laptop_3d.png', 3.4, '78%', '64%', 0.85]]],
        'speech' => ['main' => 'speech_balloon_3d.png', 'satellites' => [['globe_with_meridians_3d.png', 5.2, '-4%', '58%', 0.15], ['open_book_3d.png', 4.4, '74%', '6%', 0.6], ['left_speech_bubble_3d.png', 3, '6%', '4%', 0.9]]],
        'laptop' => ['main' => 'laptop_3d.png', 'satellites' => [['keyboard_3d.png', 5.2, '72%', '60%', 0.2], ['computer_mouse_3d.png', 4.4, '-2%', '6%', 0.55], ['light_bulb_3d.png', 3.2, '80%', '2%', 0.85]]],
        'crayons' => ['main' => 'crayon_3d.png', 'satellites' => [['artist_palette_3d.png', 5.4, '-2%', '8%', 0.2], ['pencil_3d.png', 4.4, '74%', '4%', 0.5], ['straight_ruler_3d.png', 3.4, '78%', '64%', 0.85]]],
        'calc' => ['main' => 'money_bag_3d.png', 'satellites' => [['coin_3d.png', 5.2, '-4%', '58%', 0.15], ['receipt_3d.png', 4.4, '74%', '6%', 0.6], ['abacus_3d.png', 3, '6%', '4%', 0.9]]],
        'chart' => ['main' => 'chart_increasing_3d.png', 'satellites' => [['bar_chart_3d.png', 5.2, '72%', '60%', 0.2], ['briefcase_3d.png', 4.4, '-2%', '6%', 0.55], ['bullseye_3d.png', 3.2, '80%', '2%', 0.85]]],
        'stamp' => ['main' => 'scroll_3d.png', 'satellites' => [['fountain_pen_3d.png', 5.4, '-2%', '8%', 0.2], ['page_facing_up_3d.png', 4.4, '74%', '4%', 0.5], ['locked_with_pen_3d.png', 3.4, '78%', '64%', 0.85]]],
        'sofa' => ['main' => 'couch_and_lamp_3d.png', 'satellites' => [['potted_plant_3d.png', 5.2, '-4%', '58%', 0.15], ['framed_picture_3d.png', 4.4, '74%', '6%', 0.6], ['bed_3d.png', 3, '6%', '4%', 0.9]]],
        'kitchen' => ['main' => 'cooking_3d.png', 'satellites' => [['kitchen_knife_3d.png', 5.2, '72%', '60%', 0.2], ['teapot_3d.png', 4.4, '-2%', '6%', 0.55], ['spoon_3d.png', 3.2, '80%', '2%', 0.85]]],
        'paint' => ['main' => 'paintbrush_3d.png', 'satellites' => [['artist_palette_3d.png', 5.4, '-2%', '8%', 0.2], ['bucket_3d.png', 4.4, '74%', '4%', 0.5], ['sparkles_3d.png', 3.4, '78%', '64%', 0.85]]],
        'saw' => ['main' => 'carpentry_saw_3d.png', 'satellites' => [['wood_3d.png', 5.2, '-4%', '58%', 0.15], ['hammer_3d.png', 4.4, '74%', '6%', 0.6], ['screwdriver_3d.png', 3, '6%', '4%', 0.9]]],
        'tire' => ['main' => 'racing_car_3d.png', 'satellites' => [['wrench_3d.png', 5.2, '72%', '60%', 0.2], ['gear_3d.png', 4.4, '-2%', '6%', 0.55], ['automobile_3d.png', 3.2, '80%', '2%', 0.85]]],
        'battery' => ['main' => 'battery_3d.png', 'satellites' => [['high_voltage_3d.png', 5.4, '-2%', '8%', 0.2], ['electric_plug_3d.png', 4.4, '74%', '4%', 0.5], ['automobile_3d.png', 3.4, '78%', '64%', 0.85]]],
        'oil' => ['main' => 'oil_drum_3d.png', 'satellites' => [['fuel_pump_3d.png', 5.2, '-4%', '58%', 0.15], ['droplet_3d.png', 4.4, '74%', '6%', 0.6], ['wrench_3d.png', 3, '6%', '4%', 0.9]]],
        'wash' => ['main' => 'bubbles_3d.png', 'satellites' => [['sponge_3d.png', 5.2, '72%', '60%', 0.2], ['droplet_3d.png', 4.4, '-2%', '6%', 0.55], ['sport_utility_vehicle_3d.png', 3.2, '80%', '2%', 0.85]]],
        'car' => ['main' => 'sport_utility_vehicle_3d.png', 'satellites' => [['automobile_3d.png', 5.4, '-2%', '8%', 0.2], ['key_3d.png', 4.4, '74%', '4%', 0.5], ['sparkles_3d.png', 3.4, '78%', '64%', 0.85]]],
        'rings' => ['main' => 'ring_3d.png', 'satellites' => [['wedding_3d.png', 5.2, '-4%', '58%', 0.15], ['bouquet_3d.png', 4.4, '74%', '6%', 0.6], ['heart_with_ribbon_3d.png', 3, '6%', '4%', 0.9]]],
        'partyhat' => ['main' => 'party_popper_3d.png', 'satellites' => [['balloon_3d.png', 5.2, '72%', '60%', 0.2], ['birthday_cake_3d.png', 4.4, '-2%', '6%', 0.55], ['confetti_ball_3d.png', 3.2, '80%', '2%', 0.85]]],
        'mic' => ['main' => 'studio_microphone_3d.png', 'satellites' => [['loudspeaker_3d.png', 5.4, '-2%', '8%', 0.2], ['spiral_calendar_3d.png', 4.4, '74%', '4%', 0.5], ['sparkles_3d.png', 3.4, '78%', '64%', 0.85]]],
        'spotlight' => ['main' => 'loudspeaker_3d.png', 'satellites' => [['light_bulb_3d.png', 5.2, '-4%', '58%', 0.15], ['musical_notes_3d.png', 4.4, '74%', '6%', 0.6], ['flashlight_3d.png', 3, '6%', '4%', 0.9]]],
        'jet' => ['main' => 'airplane_departure_3d.png', 'satellites' => [['luggage_3d.png', 5.2, '72%', '60%', 0.2], ['cloud_3d.png', 4.4, '-2%', '6%', 0.55], ['sun_3d.png', 3.2, '80%', '2%', 0.85]]],
        'kaaba' => ['main' => 'kaaba_3d.png', 'satellites' => [['mosque_3d.png', 5.4, '-2%', '8%', 0.2], ['prayer_beads_3d.png', 4.4, '74%', '4%', 0.5], ['star_and_crescent_3d.png', 3.4, '78%', '64%', 0.85]]],
        'beach' => ['main' => 'desert_island_3d.png', 'satellites' => [['sun_3d.png', 5.2, '-4%', '58%', 0.15], ['umbrella_on_ground_3d.png', 4.4, '74%', '6%', 0.6], ['tropical_drink_3d.png', 3, '6%', '4%', 0.9]]],
        'tent' => ['main' => 'camping_3d.png', 'satellites' => [['fire_3d.png', 5.2, '72%', '60%', 0.2], ['camel_3d.png', 4.4, '-2%', '6%', 0.55], ['night_with_stars_3d.png', 3.2, '80%', '2%', 0.85]]],
        'apps' => ['main' => 'mobile_phone_with_arrow_3d.png', 'satellites' => [['gear_3d.png', 5.4, '-2%', '8%', 0.2], ['rocket_3d.png', 4.4, '74%', '4%', 0.5], ['sparkles_3d.png', 3.4, '78%', '64%', 0.85]]],
        'servers' => ['main' => 'desktop_computer_3d.png', 'satellites' => [['cloud_3d.png', 5.2, '-4%', '58%', 0.15], ['satellite_antenna_3d.png', 4.4, '74%', '6%', 0.6], ['gear_3d.png', 3, '6%', '4%', 0.9]]],
        'padlock' => ['main' => 'locked_with_key_3d.png', 'satellites' => [['shield_3d.png', 5.2, '72%', '60%', 0.2], ['key_3d.png', 4.4, '-2%', '6%', 0.55], ['sparkles_3d.png', 3.2, '80%', '2%', 0.85]]],
        'neural' => ['main' => 'robot_3d.png', 'satellites' => [['brain_3d.png', 5.4, '-2%', '8%', 0.2], ['light_bulb_3d.png', 4.4, '74%', '4%', 0.5], ['sparkles_3d.png', 3.4, '78%', '64%', 0.85]]],
        'pencils' => ['main' => 'pencil_3d.png', 'satellites' => [['straight_ruler_3d.png', 5.2, '-4%', '58%', 0.15], ['triangular_ruler_3d.png', 4.4, '74%', '6%', 0.6], ['artist_palette_3d.png', 3, '6%', '4%', 0.9]]],
        'brackets' => ['main' => 'keyboard_3d.png', 'satellites' => [['computer_mouse_3d.png', 5.2, '72%', '60%', 0.2], ['rocket_3d.png', 4.4, '-2%', '6%', 0.55], ['gear_3d.png', 3.2, '80%', '2%', 0.85]]],
        'quill' => ['main' => 'feather_3d.png', 'satellites' => [['black_nib_3d.png', 5.4, '-2%', '8%', 0.2], ['scroll_3d.png', 4.4, '74%', '4%', 0.5], ['notebook_3d.png', 3.4, '78%', '64%', 0.85]]],
        'suit' => ['main' => 'necktie_3d.png', 'satellites' => [['coat_3d.png', 5.2, '-4%', '58%', 0.15], ['mans_shoe_3d.png', 4.4, '74%', '6%', 0.6], ['briefcase_3d.png', 3, '6%', '4%', 0.9]]],
        'heels' => ['main' => 'high_heeled_shoe_3d.png', 'satellites' => [['handbag_3d.png', 5.2, '72%', '60%', 0.2], ['clutch_bag_3d.png', 4.4, '-2%', '6%', 0.55], ['sparkles_3d.png', 3.2, '80%', '2%', 0.85]]],
        'abaya' => ['main' => 'womans_clothes_3d.png', 'satellites' => [['scarf_3d.png', 5.4, '-2%', '8%', 0.2], ['handbag_3d.png', 4.4, '74%', '4%', 0.5], ['sparkles_3d.png', 3.4, '78%', '64%', 0.85]]],
        'kidswear' => ['main' => 't_shirt_3d.png', 'satellites' => [['socks_3d.png', 5.2, '-4%', '58%', 0.15], ['teddy_bear_3d.png', 4.4, '74%', '6%', 0.6], ['balloon_3d.png', 3, '6%', '4%', 0.9]]],
        'bread' => ['main' => 'baguette_bread_3d.png', 'satellites' => [['bread_3d.png', 5.2, '72%', '60%', 0.2], ['croissant_3d.png', 4.4, '-2%', '6%', 0.55], ['pretzel_3d.png', 3.2, '80%', '2%', 0.85]]],
        'kunafa' => ['main' => 'shallow_pan_of_food_3d.png', 'satellites' => [['honey_pot_3d.png', 5.4, '-2%', '8%', 0.2], ['peanuts_3d.png', 4.4, '74%', '4%', 0.5], ['custard_3d.png', 3.4, '78%', '64%', 0.85]]],
        'donut' => ['main' => 'doughnut_3d.png', 'satellites' => [['cookie_3d.png', 5.2, '-4%', '58%', 0.15], ['cupcake_3d.png', 4.4, '74%', '6%', 0.6], ['sparkles_3d.png', 3, '6%', '4%', 0.9]]],
        'icecream' => ['main' => 'soft_ice_cream_3d.png', 'satellites' => [['ice_cream_3d.png', 5.2, '72%', '60%', 0.2], ['shaved_ice_3d.png', 4.4, '-2%', '6%', 0.55], ['cherries_3d.png', 3.2, '80%', '2%', 0.85]]],
        'rattle' => ['main' => 'baby_bottle_3d.png', 'satellites' => [['teddy_bear_3d.png', 5.4, '-2%', '8%', 0.2], ['yo_yo_3d.png', 4.4, '74%', '4%', 0.5], ['baby_chick_3d.png', 3.4, '78%', '64%', 0.85]]],
        'abc' => ['main' => 'abacus_3d.png', 'satellites' => [['crayon_3d.png', 5.2, '-4%', '58%', 0.15], ['open_book_3d.png', 4.4, '74%', '6%', 0.6], ['puzzle_piece_3d.png', 3, '6%', '4%', 0.9]]],
        'clapper' => ['main' => 'clapper_board_3d.png', 'satellites' => [['film_frames_3d.png', 5.2, '72%', '60%', 0.2], ['movie_camera_3d.png', 4.4, '-2%', '6%', 0.55], ['popcorn_3d.png', 3.2, '80%', '2%', 0.85]]],
        'drone' => ['main' => 'video_camera_3d.png', 'satellites' => [['helicopter_3d.png', 5.4, '-2%', '8%', 0.2], ['film_projector_3d.png', 4.4, '74%', '4%', 0.5], ['sparkles_3d.png', 3.4, '78%', '64%', 0.85]]],
        'polaroid' => ['main' => 'framed_picture_3d.png', 'satellites' => [['camera_3d.png', 5.2, '-4%', '58%', 0.15], ['wedding_3d.png', 4.4, '74%', '6%', 0.6], ['heart_decoration_3d.png', 3, '6%', '4%', 0.9]]],
        'incense' => ['main' => 'diya_lamp_3d.png', 'satellites' => [['prayer_beads_3d.png', 5.2, '72%', '60%', 0.2], ['wood_3d.png', 4.4, '-2%', '6%', 0.55], ['fire_3d.png', 3.2, '80%', '2%', 0.85]]],
        'soap' => ['main' => 'soap_3d.png', 'satellites' => [['bubbles_3d.png', 5.4, '-2%', '8%', 0.2], ['lotion_bottle_3d.png', 4.4, '74%', '4%', 0.5], ['sponge_3d.png', 3.4, '78%', '64%', 0.85]]],
        'cat' => ['main' => 'cat_face_3d.png', 'satellites' => [['yarn_3d.png', 5.2, '-4%', '58%', 0.15], ['fish_3d.png', 4.4, '74%', '6%', 0.6], ['paw_prints_3d.png', 3, '6%', '4%', 0.9]]],
        'dog' => ['main' => 'dog_face_3d.png', 'satellites' => [['bone_3d.png', 5.2, '72%', '60%', 0.2], ['poodle_3d.png', 4.4, '-2%', '6%', 0.55], ['paw_prints_3d.png', 3.2, '80%', '2%', 0.85]]],
        'bird' => ['main' => 'parrot_3d.png', 'satellites' => [['bird_3d.png', 5.4, '-2%', '8%', 0.2], ['nest_with_eggs_3d.png', 4.4, '74%', '4%', 0.5], ['feather_3d.png', 3.4, '78%', '64%', 0.85]]],
        'petbowl' => ['main' => 'canned_food_3d.png', 'satellites' => [['bone_3d.png', 5.2, '-4%', '58%', 0.15], ['fish_3d.png', 4.4, '74%', '6%', 0.6], ['paw_prints_3d.png', 3, '6%', '4%', 0.9]]],
    ];

    /** @return array<string, string> مفتاح => اسم الرسمة (كل الرسومات) */
    public static function all(): array
    {
        return self::ORIGINAL + array_map(fn (array $s) => $s['label'], self::scenes());
    }

    public static function has(string $key): bool
    {
        return isset(self::scenes()[$key]);
    }

    public static function exists(string $key): bool
    {
        return isset(self::all()[$key]);
    }

    /** @return array<string, mixed> */
    public static function spec(string $key): array
    {
        return self::scenes()[$key];
    }

    // رموز الألوان: p أساسي، pl فاتح، pd غامق، w أبيض، m معدن، g دهبي، wood خشب، dark غامق، glass زجاج. غير كده = CSS زي ما هو.
    public static function color(string $token): string
    {
        return match ($token) {
            'p' => 'var(--site-primary)',
            'pl' => 'color-mix(in srgb, var(--site-primary) 50%, #fff)',
            'pll' => 'color-mix(in srgb, var(--site-primary) 22%, #fff)',
            'pd' => 'color-mix(in srgb, var(--site-primary) 60%, #000)',
            'w' => '#f8fafc',
            'cream' => '#fdf4e3',
            'm' => '#cbd5e1',
            'md' => '#64748b',
            'g' => '#d4a63a',
            'gl' => '#f3d98a',
            'wood' => '#b98146',
            'woodl' => '#d9a86c',
            'dark' => '#1f2937',
            'black' => '#111318',
            'glass' => 'color-mix(in srgb, var(--site-primary) 18%, rgb(255 255 255 / .45))',
            'red' => '#ef4444', 'green' => '#22c55e', 'yellow' => '#facc15', 'orange' => '#fb923c', 'blue' => '#3b82f6',
            'pink' => '#f472b6', 'brown' => '#8b5a2b', 'skin' => '#f2c9a0', 'sand' => '#e9cf98', 'sea' => '#38bdf8',
            default => $token,
        };
    }

    /** @return list<array{class: string, style: string}> */
    public static function floaties(string $kind): array
    {
        $spots = [['6%', '14%', '0s'], ['84%', '18%', '1.2s'], ['10%', '76%', '2.1s'], ['80%', '78%', '.6s']];

        return match ($kind) {
            'none' => [],
            'hearts' => [
                ['class' => 'bq3d-heart bq3d-floaty', 'style' => 'left: 6%; top: 14%;'],
                ['class' => 'bq3d-heart bq3d-bobby', 'style' => 'right: 10%; top: 10%; scale: .6; --delay: 1.2s;'],
            ],
            'bubbles' => array_map(fn ($s) => ['class' => 'bq3d-ball bq3d-floaty', 'style' => "left: {$s[0]}; top: {$s[1]}; width: 1.5rem; height: 1.5rem; --delay: {$s[2]}; --bq3d-ball: rgb(255 255 255 / .55); opacity: .8;"], $spots),
            'sparkles' => array_map(fn ($s) => ['class' => 'bq3d-floaty', 'style' => "position: absolute; left: {$s[0]}; top: {$s[1]}; width: 1.4rem; height: 1.4rem; --delay: {$s[2]}; background-color: color-mix(in srgb, var(--site-primary) 55%, #fff); clip-path: polygon(50% 0, 62% 38%, 100% 50%, 62% 62%, 50% 100%, 38% 62%, 0 50%, 38% 38%); filter: drop-shadow(0 0 6px var(--site-primary));"], $spots),
            default => [
                ['class' => 'bq3d-ball bq3d-floaty', 'style' => 'left: 6%; top: 14%; width: 1.3rem; height: 1.3rem;'],
                ['class' => 'bq3d-ball bq3d-bobby', 'style' => 'right: 8%; bottom: 14%; width: 1rem; height: 1rem; --delay: 1s; --bq3d-ball: color-mix(in srgb, var(--site-primary) 45%, #fff);'],
            ],
        };
    }

    private static function zigzag(int $teeth, float $depth = 18): string
    {
        $pts = ['0 0', '100% 0', '100% '.(100 - $depth).'%'];
        for ($i = $teeth; $i >= 0; $i--) {
            $x = round($i * 100 / $teeth, 2);
            $pts[] = $x.'% '.($i % 2 ? '100%' : (100 - $depth).'%');
        }

        return 'polygon('.implode(', ', $pts).')';
    }

    private static function wave(): string
    {
        $pts = [];
        for ($i = 0; $i <= 20; $i++) {
            $pts[] = ($i * 5).'% '.round(35 + 25 * sin($i / 20 * 4 * M_PI), 1).'%';
        }

        return 'polygon('.implode(', ', $pts).', 100% 100%, 0 100%)';
    }

    private const TRI = 'polygon(50% 0, 100% 100%, 0 100%)';
    private const HEART = 'polygon(50% 100%, 6% 55%, 0 30%, 6% 10%, 22% 0, 38% 4%, 50% 18%, 62% 4%, 78% 0, 94% 10%, 100% 30%, 94% 55%)';
    private const DROP = 'polygon(50% 0, 72% 32%, 86% 52%, 92% 68%, 88% 84%, 76% 96%, 50% 100%, 24% 96%, 12% 84%, 8% 68%, 14% 52%, 28% 32%)';

    /** @return array<string, array<string, mixed>> */
    private static function scenes(): array
    {
        static $scenes = null;

        return $scenes ??= self::build();
    }

    /** @return array<string, array<string, mixed>> */
    private static function build(): array
    {

        return [
            // ================= مطاعم وكافيهات =================
            'dish' => ['label' => 'طبق وشوكة', 'tilt' => [-30, -20], 'parts' => [
                ['cyl', 'r' => 5, 'h' => .45, 'n' => 24, 'c' => 'w', 'cap' => 'radial-gradient(circle, #fff 0 55%, var(--site-primary) 56% 58%, #f1f5f9 59%)', 't' => 'translateY(1.5rem)'],
                ['ball', 's' => 3.2, 'c' => 'orange', 't' => 'translate3d(-.8rem, 0, .5rem)'],
                ['ball', 's' => 2.4, 'c' => 'green', 't' => 'translate3d(1.2rem, .4rem, -.8rem)'],
                ['ball', 's' => 1.8, 'c' => 'red', 't' => 'translate3d(.6rem, -.4rem, 1.4rem)'],
                ['box', 'w' => .35, 'h' => 7, 'd' => .2, 'c' => 'm', 't' => 'translate3d(-6.2rem, -1rem, 0) rotateZ(-8deg)'],
                ['box', 'w' => .6, 'h' => 7, 'd' => .15, 'c' => 'm', 't' => 'translate3d(6.2rem, -1rem, 0) rotateZ(8deg)'],
            ]],
            'beanpot' => ['label' => 'قدرة فول', 'tilt' => [-16, -25], 'parts' => [
                ['cyl', 'r' => 3.4, 'h' => 4.4, 'n' => 22, 'c' => '#b87333', 'cap' => '#7a4a24', 't' => 'translateY(1rem)'],
                ['cyl', 'r' => 3.55, 'h' => .45, 'n' => 22, 'c' => '#c9864a', 't' => 'translateY(-1.4rem)'],
                ['ball', 's' => 1.3, 'c' => '#c9864a', 't' => 'translateY(-2.1rem)'],
                ['shape', 'w' => 2, 'h' => 1.6, 'c' => '#9a5b2c', 'rad' => '50%', 'hole' => '55%', 'layers' => 4, 't' => 'translate3d(-4rem, -.2rem, 0) rotateY(90deg)'],
                ['shape', 'w' => 2, 'h' => 1.6, 'c' => '#9a5b2c', 'rad' => '50%', 'hole' => '55%', 'layers' => 4, 't' => 'translate3d(4rem, -.2rem, 0) rotateY(90deg)'],
                ['steam', 't' => 'translate(-.8rem, -1.5rem)'], ['steam', 't' => 'translate(.8rem, -1.5rem)', 'delay' => '1.4s'],
            ]],
            'pizza' => ['label' => 'بيتزا', 'anim' => 'spin', 'tilt' => [-50, 0], 'parts' => [
                ['cyl', 'r' => 5.5, 'h' => .6, 'n' => 26, 'c' => '#e0a050', 'cap' => 'radial-gradient(circle at 30% 35%, #b91c1c 0 7%, transparent 7.5%), radial-gradient(circle at 65% 30%, #b91c1c 0 7%, transparent 7.5%), radial-gradient(circle at 55% 65%, #b91c1c 0 7%, transparent 7.5%), radial-gradient(circle at 28% 68%, #166534 0 4%, transparent 4.5%), radial-gradient(circle at 72% 55%, #166534 0 4%, transparent 4.5%), radial-gradient(circle, #fbbf24 0 78%, #c2410c 80% 100%)'],
            ]],
            'skewer' => ['label' => 'سيخ مشويات', 'tilt' => [-14, -30], 'parts' => [
                ['group', 't' => 'rotateZ(-14deg)', 'parts' => [
                    ['box', 'w' => 14, 'h' => .25, 'd' => .25, 'c' => 'm'],
                    ['group', 'anim' => 'bq3d-spinx', 'parts' => [
                        ['box', 'w' => 1.8, 'h' => 1.8, 'd' => 1.8, 'c' => '#7c3f1d', 't' => 'translateX(-4rem)'],
                        ['box', 'w' => 1.4, 'h' => 1.6, 'd' => 1.6, 'c' => 'green', 't' => 'translateX(-2.2rem)'],
                        ['box', 'w' => 1.8, 'h' => 1.8, 'd' => 1.8, 'c' => '#8a4a22', 't' => 'translateX(-.4rem)'],
                        ['box', 'w' => 1.4, 'h' => 1.6, 'd' => 1.6, 'c' => 'red', 't' => 'translateX(1.4rem)'],
                        ['box', 'w' => 1.8, 'h' => 1.8, 'd' => 1.8, 'c' => '#7c3f1d', 't' => 'translateX(3.2rem)'],
                    ]],
                    ['ball', 's' => .9, 'c' => 'md', 't' => 'translateX(7rem)'],
                ]],
                ['steam', 't' => 'translate(-1rem, -2.4rem)'], ['steam', 't' => 'translate(1.5rem, -3rem)', 'delay' => '1.2s'],
            ]],
            'juice' => ['label' => 'كوباية عصير', 'tilt' => [-14, -20], 'parts' => [
                ['cyl', 'r' => 2.3, 'h' => 4.6, 'n' => 20, 'c' => 'orange', 'cap' => '#fdba74', 't' => 'translateY(.9rem)'],
                ['cyl', 'r' => 2.5, 'h' => 6.6, 'n' => 20, 'c' => 'rgb(255 255 255 / .28)', 'caps' => false],
                ['box', 'w' => .35, 'h' => 6.5, 'd' => .35, 'c' => 'p', 't' => 'translate3d(.9rem, -3.4rem, 0) rotateZ(14deg)'],
                ['shape', 'w' => 3, 'h' => 3, 'c' => 'yellow', 'rad' => '50%', 'front' => 'repeating-conic-gradient(#fef08a 0 20deg, #facc15 20deg 40deg)', 'layers' => 3, 't' => 'translate3d(-2.5rem, -3.3rem, 0) rotateZ(-20deg)'],
            ]],

            // ================= عيادات =================
            'tooth' => ['label' => 'سنة', 'floaties' => 'sparkles', 'tilt' => [-8, -25], 'parts' => [
                ['shape', 'w' => 7.5, 'h' => 9, 'c' => 'w', 'layers' => 10, 'step' => .2, 'clip' => 'polygon(18% 0, 38% 6%, 50% 4%, 62% 6%, 82% 0, 96% 12%, 98% 36%, 88% 58%, 82% 100%, 66% 100%, 58% 70%, 50% 64%, 42% 70%, 34% 100%, 18% 100%, 12% 58%, 2% 36%, 4% 12%)', 'front' => 'radial-gradient(ellipse at 30% 25%, #fff 0 18%, transparent 40%), linear-gradient(#ffffff, #e2e8f0)'],
            ]],
            'stetho' => ['label' => 'قلب وسماعة', 'floaties' => 'hearts', 'tilt' => [-8, -25], 'parts' => [
                ['shape', 'w' => 7, 'h' => 6.4, 'c' => 'p', 'clip' => self::HEART, 'layers' => 9, 'step' => .2],
                ['shape', 'w' => 8, 'h' => 8, 'c' => 'dark', 'rad' => '50%', 'hole' => '88%', 'layers' => 3, 't' => 'translate3d(2.2rem, 3.8rem, 1rem)'],
                ['cyl', 'r' => 1.3, 'h' => .7, 'n' => 16, 'c' => 'm', 't' => 'translate3d(4.2rem, 6.6rem, 1rem) rotateX(90deg)'],
            ]],
            'joint' => ['label' => 'عضمة ومفصل', 'tilt' => [-10, -30], 'parts' => [
                ['group', 't' => 'rotateZ(-35deg)', 'parts' => [
                    ['cyl', 'r' => .9, 'h' => 7, 'n' => 14, 'c' => '#efe6d2', 'caps' => false],
                    ['ball', 's' => 2.6, 'c' => '#efe6d2', 't' => 'translate(-1.1rem, -3.8rem)'],
                    ['ball', 's' => 2.6, 'c' => '#efe6d2', 't' => 'translate(1.1rem, -3.8rem)'],
                    ['ball', 's' => 2.6, 'c' => '#efe6d2', 't' => 'translate(-1.1rem, 3.8rem)'],
                    ['ball', 's' => 2.6, 'c' => '#efe6d2', 't' => 'translate(1.1rem, 3.8rem)'],
                ]],
                ['shape', 'w' => 3, 'h' => 3, 'c' => 'p', 'clip' => 'polygon(35% 0, 65% 0, 65% 35%, 100% 35%, 100% 65%, 65% 65%, 65% 100%, 35% 100%, 35% 65%, 0 65%, 0 35%, 35% 35%)', 'layers' => 5, 't' => 'translate3d(4rem, -4rem, 1rem)'],
            ]],
            'eye' => ['label' => 'عين', 'anim' => 'float', 'tilt' => [-4, -14], 'parts' => [
                ['ball', 's' => 8, 'c' => '#f8fafc'],
                ['plane', 'w' => 4.2, 'h' => 4.2, 'rad' => '50%', 'c' => 'radial-gradient(circle, #0b1220 0 30%, var(--site-primary) 32% 62%, color-mix(in srgb, var(--site-primary) 60%, #000) 64% 70%, transparent 71%)', 't' => 'translateZ(3.8rem)'],
                ['plane', 'w' => 1, 'h' => 1, 'rad' => '50%', 'c' => 'rgb(255 255 255 / .85)', 't' => 'translate3d(-.8rem, -.9rem, 3.9rem)'],
                ['shape', 'w' => 11, 'h' => 4.4, 'c' => 'dark', 'rad' => '50%', 'hole' => '80%', 'layers' => 3, 't' => 'translate3d(0, 0, 4.2rem)'],
            ]],
            'teddy' => ['label' => 'دبدوب', 'anim' => 'float', 'tilt' => [0, 0], 'floaties' => 'hearts', 'parts' => [
                ['ball', 's' => 7, 'c' => '#b07842', 't' => 'translateY(3rem)'],
                ['ball', 's' => 2.3, 'c' => '#a06a38', 't' => 'translate3d(-2.6rem, -3.2rem, -.1rem)'],
                ['ball', 's' => 2.3, 'c' => '#a06a38', 't' => 'translate3d(2.6rem, -3.2rem, -.1rem)'],
                ['ball', 's' => 5.6, 'c' => '#b98146', 't' => 'translateY(-1.2rem)'],
                ['ball', 's' => 2.2, 'c' => '#e9c79a', 't' => 'translate3d(0, -.3rem, .2rem)'],
                ['ball', 's' => .8, 'c' => 'black', 't' => 'translate3d(0, -.8rem, .3rem)'],
                ['ball', 's' => .7, 'c' => 'black', 't' => 'translate3d(-1.1rem, -2rem, .3rem)'],
                ['ball', 's' => .7, 'c' => 'black', 't' => 'translate3d(1.1rem, -2rem, .3rem)'],
                ['plane', 'w' => 3, 'h' => 1.2, 'c' => 'p', 'rad' => '40%', 't' => 'translate3d(0, 1.5rem, .4rem)'],
            ]],
            'capsule' => ['label' => 'كبسولات دوا', 'tilt' => [-14, -20], 'parts' => [
                ['group', 't' => 'rotateZ(40deg)', 'parts' => [
                    ['cyl', 'r' => 1.4, 'h' => 3, 'n' => 18, 'c' => 'p', 'caps' => false, 't' => 'translateY(-1.5rem)'],
                    ['cyl', 'r' => 1.4, 'h' => 3, 'n' => 18, 'c' => 'w', 'caps' => false, 't' => 'translateY(1.5rem)'],
                    ['ball', 's' => 2.8, 'c' => 'p', 't' => 'translateY(-3rem)'],
                    ['ball', 's' => 2.8, 'c' => 'w', 't' => 'translateY(3rem)'],
                ]],
                ['group', 't' => 'translate3d(4.5rem, 3rem, -2rem) rotateZ(-30deg) scale(.6)', 'parts' => [
                    ['cyl', 'r' => 1.4, 'h' => 3, 'n' => 14, 'c' => 'orange', 'caps' => false, 't' => 'translateY(-1.5rem)'],
                    ['cyl', 'r' => 1.4, 'h' => 3, 'n' => 14, 'c' => 'w', 'caps' => false, 't' => 'translateY(1.5rem)'],
                    ['ball', 's' => 2.8, 'c' => 'orange', 't' => 'translateY(-3rem)'], ['ball', 's' => 2.8, 'c' => 'w', 't' => 'translateY(3rem)'],
                ]],
                ['cyl', 'r' => 1.6, 'h' => .7, 'n' => 18, 'c' => 'w', 't' => 'translate3d(-4.5rem, 3.6rem, 1rem)'],
            ]],
            'tubes' => ['label' => 'أنابيب تحاليل', 'tilt' => [-12, -24], 'parts' => [
                ['box', 'w' => 9, 'h' => .7, 'd' => 2.6, 'c' => 'wood', 't' => 'translateY(1.2rem)'],
                ['box', 'w' => 9, 'h' => .5, 'd' => 2.6, 'c' => 'wood', 't' => 'translateY(-1.8rem)'],
                ['cyl', 'r' => .75, 'h' => 6.4, 'n' => 12, 'c' => 'rgb(255 255 255 / .35)', 'caps' => false, 't' => 'translateX(-3rem)'],
                ['cyl', 'r' => .7, 'h' => 3, 'n' => 12, 'c' => 'red', 't' => 'translate(-3rem, 1.6rem)'],
                ['cyl', 'r' => .75, 'h' => 6.4, 'n' => 12, 'c' => 'rgb(255 255 255 / .35)', 'caps' => false],
                ['cyl', 'r' => .7, 'h' => 4, 'n' => 12, 'c' => 'p', 't' => 'translateY(1.1rem)'],
                ['cyl', 'r' => .75, 'h' => 6.4, 'n' => 12, 'c' => 'rgb(255 255 255 / .35)', 'caps' => false, 't' => 'translateX(3rem)'],
                ['cyl', 'r' => .7, 'h' => 2.2, 'n' => 12, 'c' => 'green', 't' => 'translate(3rem, 2rem)'],
            ]],
            'drop' => ['label' => 'قطرة لامعة', 'floaties' => 'sparkles', 'tilt' => [-6, -22], 'parts' => [
                ['shape', 'w' => 6.4, 'h' => 8.6, 'c' => 'pl', 'clip' => self::DROP, 'layers' => 10, 'step' => .22, 'front' => 'radial-gradient(ellipse at 35% 60%, rgb(255 255 255 / .7) 0 10%, transparent 30%)'],
                ['cyl', 'r' => 2.4, 'h' => 2, 'n' => 18, 'c' => 'w', 'cap' => 'p', 't' => 'translate3d(4rem, 3.6rem, 1rem)'],
            ]],

            // ================= صالونات =================
            'barber' => ['label' => 'عمود الحلاق ومقص', 'tilt' => [-10, -20], 'parts' => [
                ['cyl', 'r' => 1.4, 'h' => 8, 'n' => 16, 'c' => 'w', 'caps' => false, 'stripe' => 'repeating-linear-gradient(160deg, #ef4444 0 .7rem, #fff .7rem 1.4rem, #2563eb 1.4rem 2.1rem, #fff 2.1rem 2.8rem)', 't' => 'translateX(-2rem)'],
                ['ball', 's' => 2.4, 'c' => 'm', 't' => 'translate(-2rem, -4.6rem)'],
                ['cyl', 'r' => 1.7, 'h' => .8, 'n' => 16, 'c' => 'md', 't' => 'translate(-2rem, 4.3rem)'],
                ['group', 't' => 'translate3d(3.4rem, .5rem, 1rem) rotateZ(20deg)', 'parts' => [
                    ['shape', 'w' => 1, 'h' => 6, 'c' => 'm', 'clip' => 'polygon(50% 0, 100% 100%, 0 100%)', 'layers' => 3, 't' => 'rotateZ(12deg) translateY(-2.5rem)'],
                    ['shape', 'w' => 1, 'h' => 6, 'c' => 'm', 'clip' => 'polygon(50% 0, 100% 100%, 0 100%)', 'layers' => 3, 't' => 'rotateZ(-12deg) translateY(-2.5rem)'],
                    ['shape', 'w' => 2, 'h' => 2, 'c' => 'p', 'rad' => '50%', 'hole' => '55%', 'layers' => 3, 't' => 'translate(-1rem, 1.5rem)'],
                    ['shape', 'w' => 2, 'h' => 2, 'c' => 'p', 'rad' => '50%', 'hole' => '55%', 'layers' => 3, 't' => 'translate(1rem, 1.5rem)'],
                ]],
            ]],
            'polish' => ['label' => 'زجاجة مانيكير', 'floaties' => 'sparkles', 'tilt' => [-14, -30], 'parts' => [
                ['box', 'w' => 4.4, 'h' => 4, 'd' => 4.4, 'c' => 'p', 'front' => 'linear-gradient(120deg, rgb(255 255 255 / .45), transparent 40%)', 't' => 'translateY(1.8rem)'],
                ['cyl', 'r' => .9, 'h' => .8, 'n' => 12, 'c' => 'g', 't' => 'translateY(-.6rem)'],
                ['cyl', 'r' => .85, 'h' => 4.2, 'n' => 14, 'c' => 'black', 't' => 'translateY(-3rem)'],
                ['plane', 'w' => 3, 'h' => 3, 'rad' => '50% 50% 50% 0', 'c' => 'p', 't' => 'translate3d(4.2rem, 3.6rem, 1rem) rotateX(70deg)'],
            ]],
            'stones' => ['label' => 'أحجار سبا وشمعة', 'anim' => 'float', 'tilt' => [-14, -20], 'parts' => [
                ['shape', 'w' => 8, 'h' => 2.6, 'c' => '#475569', 'rad' => '50%', 'layers' => 5, 't' => 'translateY(3rem)'],
                ['shape', 'w' => 6.4, 'h' => 2.2, 'c' => '#64748b', 'rad' => '50%', 'layers' => 5, 't' => 'translateY(.8rem)'],
                ['shape', 'w' => 4.8, 'h' => 1.9, 'c' => '#334155', 'rad' => '50%', 'layers' => 5, 't' => 'translateY(-1.1rem)'],
                ['shape', 'w' => 3.2, 'h' => 1.5, 'c' => '#94a3b8', 'rad' => '50%', 'layers' => 5, 't' => 'translateY(-2.7rem)'],
                ['shape', 'w' => 2.4, 'h' => 1.2, 'c' => 'green', 'clip' => 'polygon(0 100%, 30% 20%, 50% 0, 70% 20%, 100% 100%)', 'layers' => 2, 't' => 'translate3d(-.6rem, -3.9rem, 0) rotateZ(-30deg)'],
                ['cyl', 'r' => 1.2, 'h' => 2.4, 'n' => 14, 'c' => 'cream', 't' => 'translate3d(4.8rem, 2.4rem, 1rem)'],
                ['flame', 't' => 'translate3d(4.8rem, .6rem, 1rem)'],
            ]],
            'lipstick' => ['label' => 'روج', 'floaties' => 'sparkles', 'tilt' => [-12, -25], 'parts' => [
                ['cyl', 'r' => 1.6, 'h' => 4, 'n' => 16, 'c' => 'g', 't' => 'translateY(2.4rem)'],
                ['cyl', 'r' => 1.3, 'h' => 1.6, 'n' => 16, 'c' => 'gl', 't' => 'translateY(-.4rem)'],
                ['cyl', 'r' => 1.1, 'h' => 2.6, 'n' => 16, 'c' => 'p', 't' => 'translateY(-2.5rem)'],
                ['shape', 'w' => 2.2, 'h' => 1.6, 'c' => 'p', 'clip' => 'polygon(0 100%, 0 60%, 100% 0, 100% 100%)', 'layers' => 6, 'step' => .36, 't' => 'translateY(-4.6rem)'],
            ]],

            // ================= جيم =================
            'yoga' => ['label' => 'كورة يوجا ودمبل', 'anim' => 'float', 'tilt' => [-10, -20], 'parts' => [
                ['ball', 's' => 8, 'c' => 'pl'],
                ['group', 't' => 'translate3d(3.6rem, 3.8rem, 2rem) rotateZ(-20deg)', 'parts' => [
                    ['cyl', 'r' => .35, 'h' => 4, 'n' => 10, 'c' => 'm', 't' => 'rotateZ(90deg)'],
                    ['cyl', 'r' => 1.1, 'h' => 1.2, 'n' => 14, 'c' => 'p', 't' => 'translateX(-2rem) rotateZ(90deg)'],
                    ['cyl', 'r' => 1.1, 'h' => 1.2, 'n' => 14, 'c' => 'p', 't' => 'translateX(2rem) rotateZ(90deg)'],
                ]],
            ]],
            'boxing' => ['label' => 'جوانتي وكيس ملاكمة', 'tilt' => [-10, -30], 'parts' => [
                ['box', 'w' => .2, 'h' => 2.6, 'd' => .2, 'c' => 'md', 't' => 'translate(-2rem, -6rem)'],
                ['cyl', 'r' => 2.4, 'h' => 8, 'n' => 18, 'c' => 'p', 'cap' => 'pd', 't' => 'translateX(-2rem)'],
                ['shape', 'w' => 4.6, 'h' => 5.4, 'c' => 'red', 'rad' => '48% 48% 34% 34%', 'layers' => 9, 'step' => .3, 't' => 'translate3d(3.2rem, 1.4rem, 2rem) rotateZ(-15deg)'],
                ['shape', 'w' => 3.8, 'h' => 1.4, 'c' => 'w', 'rad' => '.3rem', 'layers' => 9, 'step' => .3, 't' => 'translate3d(3.8rem, 4.2rem, 2rem) rotateZ(-15deg)'],
            ]],
            'mat' => ['label' => 'مات يوجا', 'tilt' => [-20, -25], 'parts' => [
                ['plane', 'w' => 6, 'h' => 10, 'c' => 'repeating-linear-gradient(0deg, var(--site-primary) 0 1.2rem, color-mix(in srgb, var(--site-primary) 80%, #fff) 1.2rem 1.4rem)', 'rad' => '.4rem', 't' => 'translate3d(1rem, 3rem, 2rem) rotateX(80deg)'],
                ['cyl', 'r' => 1.6, 'h' => 6, 'n' => 18, 'c' => 'p', 'cap' => 'repeating-radial-gradient(circle, var(--site-primary) 0 .25rem, color-mix(in srgb, var(--site-primary) 60%, #fff) .25rem .35rem)', 't' => 'translate3d(1rem, 1.4rem, -2.8rem) rotateZ(90deg)'],
                ['ball', 's' => 2.2, 'c' => 'pl', 't' => 'translate3d(-4rem, 2rem, 1rem)'],
            ]],
            'swim' => ['label' => 'عوامة وموج', 'anim' => 'float', 'tilt' => [-8, -14], 'parts' => [
                ['shape', 'w' => 7.4, 'h' => 7.4, 'c' => 'repeating-conic-gradient(#ef4444 0 45deg, #fff 45deg 90deg)', 'rad' => '50%', 'hole' => '48%', 'layers' => 8, 'step' => .2, 't' => 'translateY(-.6rem)'],
                ['shape', 'w' => 13, 'h' => 3, 'c' => 'sea', 'clip' => self::wave(), 'layers' => 3, 't' => 'translate3d(0, 3.2rem, 1.6rem)'],
                ['shape', 'w' => 13, 'h' => 3, 'c' => 'color-mix(in srgb, #38bdf8 70%, #fff)', 'clip' => self::wave(), 'layers' => 3, 't' => 'translate3d(1.2rem, 3.8rem, 2.6rem)'],
            ]],

            // ================= عقارات =================
            'villa' => ['label' => 'فيلا بحمام سباحة', 'tilt' => [-20, -32], 'parts' => [
                ['box', 'w' => 8, 'h' => 4, 'd' => 5, 'c' => 'w', 'front' => 'linear-gradient(90deg, transparent 15%, #7dd3fc 15% 35%, transparent 35% 60%, #7dd3fc 60% 85%, transparent 85%)', 't' => 'translateY(1.2rem)'],
                ['shape', 'w' => 9, 'h' => 2.8, 'c' => 'p', 'clip' => self::TRI, 'layers' => 14, 'step' => .38, 't' => 'translateY(-2.2rem)'],
                ['box', 'w' => 4, 'h' => 3.4, 'd' => 4, 'c' => 'cream', 't' => 'translate3d(5.6rem, 1.5rem, 0)'],
                ['plane', 'w' => 9, 'h' => 3.4, 'c' => 'linear-gradient(135deg, #38bdf8, #0ea5e9)', 'rad' => '.4rem', 't' => 'translate3d(.8rem, 3.25rem, 4.6rem) rotateX(90deg)'],
            ]],
            'chalet' => ['label' => 'شاليه ع البحر', 'tilt' => [-16, -28], 'parts' => [
                ['plane', 'w' => 14, 'h' => 8, 'c' => 'linear-gradient(#38bdf8 0 40%, #e9cf98 40%)', 'rad' => '1rem', 't' => 'translateY(3rem) rotateX(90deg)'],
                ['box', 'w' => 5, 'h' => 3.2, 'd' => 4, 'c' => 'woodl', 't' => 'translate3d(-2rem, 1.4rem, 1rem)'],
                ['shape', 'w' => 6, 'h' => 2.4, 'c' => 'p', 'clip' => self::TRI, 'layers' => 12, 'step' => .36, 't' => 'translate3d(-2rem, -1.4rem, 1rem)'],
                ['box', 'w' => .25, 'h' => 5.5, 'd' => .25, 'c' => 'w', 't' => 'translate3d(4rem, .3rem, 2rem)'],
                ['cyl', 'r' => 2.8, 'h' => .3, 'n' => 16, 'c' => 'p', 'cap' => 'repeating-conic-gradient(var(--site-primary) 0 30deg, #fff 30deg 60deg)', 't' => 'translate3d(4rem, -2.4rem, 2rem) rotateZ(-10deg)'],
            ]],
            'storefront' => ['label' => 'محل تجاري', 'tilt' => [-12, -26], 'parts' => [
                ['box', 'w' => 10, 'h' => 6, 'd' => 4, 'c' => 'w', 'front' => 'linear-gradient(90deg, #bae6fd 0 60%, transparent 60% 64%, #334155 64% 90%, transparent 90%)', 't' => 'translateY(1.4rem)'],
                ['box', 'w' => 10.6, 'h' => 1.3, 'd' => 4.4, 'c' => 'pd', 't' => 'translateY(-2.2rem)'],
                ['plane', 'w' => 10.6, 'h' => 2.6, 'c' => 'repeating-linear-gradient(90deg, var(--site-primary) 0 1.06rem, #fff 1.06rem 2.12rem)', 't' => 'translate3d(0, -1rem, 3.2rem) rotateX(45deg)'],
            ]],
            'key' => ['label' => 'مفتاح بيت', 'anim' => 'spin', 'tilt' => [-8, 0], 'floaties' => 'sparkles', 'parts' => [
                ['shape', 'w' => 5, 'h' => 5, 'c' => 'g', 'rad' => '50%', 'hole' => '45%', 'layers' => 4, 't' => 'translateY(-3rem)'],
                ['box', 'w' => 1, 'h' => 7, 'd' => .6, 'c' => 'g', 't' => 'translateY(2.4rem)'],
                ['box', 'w' => 1.6, 'h' => .8, 'd' => .6, 'c' => 'g', 't' => 'translate(1.2rem, 4.6rem)'],
                ['box', 'w' => 1.2, 'h' => .8, 'd' => .6, 'c' => 'g', 't' => 'translate(1rem, 3.2rem)'],
                ['shape', 'w' => 2.4, 'h' => 2, 'c' => 'p', 'clip' => 'polygon(50% 0, 100% 45%, 85% 45%, 85% 100%, 15% 100%, 15% 45%, 0 45%)', 'layers' => 3, 't' => 'translateY(-3rem)'],
            ]],

            // ================= متاجر =================
            'dairy' => ['label' => 'كرتونة لبن وجبنة', 'tilt' => [-14, -32], 'parts' => [
                ['box', 'w' => 3.8, 'h' => 6, 'd' => 3.8, 'c' => 'w', 'face' => 'linear-gradient(transparent 55%, var(--site-primary) 55% 75%, transparent 75%)', 't' => 'translate(-1.4rem, .4rem)'],
                ['shape', 'w' => 3.8, 'h' => 1.8, 'c' => 'w', 'clip' => self::TRI, 'layers' => 10, 'step' => .38, 't' => 'translate(-1.4rem, -3.5rem)'],
                ['shape', 'w' => 5, 'h' => 3.4, 'c' => 'yellow', 'clip' => 'polygon(0 100%, 100% 0, 100% 100%)', 'layers' => 10, 'step' => .3, 'front' => 'radial-gradient(circle at 70% 70%, #ca8a04 0 7%, transparent 8%), radial-gradient(circle at 85% 45%, #ca8a04 0 6%, transparent 7%), radial-gradient(circle at 55% 88%, #ca8a04 0 5%, transparent 6%)', 't' => 'translate3d(3.2rem, 2.2rem, 2rem)'],
            ]],
            'spice' => ['label' => 'برطمانات عطارة', 'tilt' => [-14, -20], 'parts' => [
                ['cyl', 'r' => 1.7, 'h' => 2.8, 'n' => 16, 'c' => '#c2410c', 't' => 'translate(-3.8rem, 1.6rem)'],
                ['cyl', 'r' => 1.8, 'h' => 4, 'n' => 16, 'c' => 'rgb(255 255 255 / .28)', 'caps' => false, 't' => 'translate(-3.8rem, 1rem)'],
                ['cyl', 'r' => 1.9, 'h' => .7, 'n' => 16, 'c' => 'wood', 't' => 'translate(-3.8rem, -1.3rem)'],
                ['cyl', 'r' => 1.7, 'h' => 3.6, 'n' => 16, 'c' => '#eab308', 't' => 'translateY(.4rem)'],
                ['cyl', 'r' => 1.8, 'h' => 4.6, 'n' => 16, 'c' => 'rgb(255 255 255 / .28)', 'caps' => false, 't' => 'translateY(-.1rem)'],
                ['cyl', 'r' => 1.9, 'h' => .7, 'n' => 16, 'c' => 'wood', 't' => 'translateY(-2.7rem)'],
                ['cyl', 'r' => 1.7, 'h' => 2.4, 'n' => 16, 'c' => '#4d7c0f', 't' => 'translate(3.8rem, 1.8rem)'],
                ['cyl', 'r' => 1.8, 'h' => 3.6, 'n' => 16, 'c' => 'rgb(255 255 255 / .28)', 'caps' => false, 't' => 'translate(3.8rem, 1.2rem)'],
                ['cyl', 'r' => 1.9, 'h' => .7, 'n' => 16, 'c' => 'wood', 't' => 'translate(3.8rem, -.9rem)'],
            ]],
            'cart' => ['label' => 'عربية تسوق', 'tilt' => [-16, -30], 'parts' => [
                ['box', 'w' => 7.6, 'h' => 4, 'd' => 4.6, 'c' => 'rgb(148 163 184 / .35)', 'face' => 'repeating-linear-gradient(90deg, #64748b 0 2px, transparent 2px .8rem), repeating-linear-gradient(0deg, #64748b 0 2px, transparent 2px .8rem)'],
                ['ball', 's' => 2, 'c' => 'red', 't' => 'translate3d(-2rem, -2.2rem, .5rem)'],
                ['ball', 's' => 1.8, 'c' => 'green', 't' => 'translate3d(.2rem, -2.4rem, -.6rem)'],
                ['box', 'w' => 1.6, 'h' => 2.6, 'd' => 1.2, 'c' => 'p', 't' => 'translate3d(2rem, -2.6rem, 0)'],
                ['box', 'w' => .3, 'h' => 3.6, 'd' => .3, 'c' => 'md', 't' => 'translate3d(4.6rem, -2rem, 0) rotateZ(-20deg)'],
                ['cyl', 'r' => .55, 'h' => .4, 'n' => 10, 'c' => 'dark', 't' => 'translate3d(-3rem, 2.8rem, 1.8rem) rotateX(90deg)'],
                ['cyl', 'r' => .55, 'h' => .4, 'n' => 10, 'c' => 'dark', 't' => 'translate3d(3rem, 2.8rem, 1.8rem) rotateX(90deg)'],
            ]],
            'phone' => ['label' => 'موبايل', 'tilt' => [-8, -28], 'floaties' => 'sparkles', 'parts' => [
                ['box', 'w' => 5, 'h' => 9.6, 'd' => .55, 'c' => 'dark', 'front' => 'linear-gradient(160deg, var(--site-primary), color-mix(in srgb, var(--site-primary) 30%, #0f172a))'],
                ['plane', 'w' => 1.4, 'h' => .35, 'rad' => '1rem', 'c' => '#0b0f17', 't' => 'translate3d(0, -4.2rem, .3rem)'],
                ['ball', 's' => 1.6, 'c' => 'w', 't' => 'translate3d(3.8rem, 3rem, 1rem)'],
                ['ball', 's' => 1.6, 'c' => 'w', 't' => 'translate3d(4.8rem, 4.2rem, .4rem)'],
            ]],

            // ================= تعليم =================
            'speech' => ['label' => 'فقاعات كلام (لغات)', 'anim' => 'float', 'tilt' => [-6, -20], 'parts' => [
                ['shape', 'w' => 6.4, 'h' => 4.6, 'c' => 'p', 'clip' => 'polygon(0 0, 100% 0, 100% 75%, 45% 75%, 22% 100%, 26% 75%, 0 75%)', 'layers' => 6, 'step' => .2, 't' => 'translate(-1.8rem, -1.6rem)'],
                ['glyph', 'text' => 'ع', 'fs' => 2.6, 'c' => '#fff', 'w' => 6.4, 'h' => 3.4, 'layers' => 1, 't' => 'translate3d(-1.8rem, -2.2rem, .6rem)'],
                ['shape', 'w' => 5.4, 'h' => 4, 'c' => 'pl', 'clip' => 'polygon(0 0, 100% 0, 100% 75%, 74% 75%, 78% 100%, 55% 75%, 0 75%)', 'layers' => 6, 'step' => .2, 't' => 'translate3d(2.4rem, 2.4rem, 1.4rem)'],
                ['glyph', 'text' => 'A', 'fs' => 2.2, 'c' => 'var(--site-primary)', 'w' => 5.4, 'h' => 3, 'layers' => 1, 't' => 'translate3d(2.4rem, 1.9rem, 2rem)'],
            ]],
            'laptop' => ['label' => 'لابتوب', 'tilt' => [-16, -30], 'parts' => [
                ['box', 'w' => 9, 'h' => .45, 'd' => 6, 'c' => 'm', 'face' => 'none', 't' => 'translateY(2.4rem)'],
                ['group', 't' => 'translate3d(0, 2.2rem, -3rem) rotateX(-12deg)', 'parts' => [
                    ['box', 'w' => 9, 'h' => 6, 'd' => .3, 'c' => 'md', 'front' => 'linear-gradient(#0f172a, #0f172a)', 't' => 'translateY(-3rem)'],
                    ['plane', 'w' => 7.6, 'h' => 4.6, 'c' => 'repeating-linear-gradient(transparent 0 .5rem, color-mix(in srgb, var(--site-primary) 80%, #fff) .5rem .7rem, transparent .7rem .95rem) 0 .4rem / 70% 100% no-repeat, #0b1220', 't' => 'translate3d(0, -3rem, .2rem)'],
                ]],
            ]],
            'crayons' => ['label' => 'ألوان شمع', 'tilt' => [-10, -20], 'floaties' => 'sparkles', 'parts' => array_map(fn ($i) => ['group', 't' => 'rotateZ('.(-36 + $i * 18).'deg) translateY(-1rem)', 'parts' => [
                ['cyl', 'r' => .65, 'h' => 6, 'n' => 10, 'c' => ['#ef4444', '#f59e0b', '#22c55e', '#3b82f6', 'var(--site-primary)'][$i]],
                ['shape', 'w' => 1.3, 'h' => 1.6, 'c' => ['#ef4444', '#f59e0b', '#22c55e', '#3b82f6', 'var(--site-primary)'][$i], 'clip' => self::TRI, 'layers' => 4, 'step' => .3, 't' => 'translateY(-3.8rem)'],
            ]], range(0, 4))],

            // ================= استشارات =================
            'calc' => ['label' => 'آلة حاسبة وعملات', 'tilt' => [-14, -26], 'parts' => [
                ['box', 'w' => 5.6, 'h' => 8, 'd' => 1, 'c' => 'dark', 'front' => 'linear-gradient(#a7f3d0, #a7f3d0) .6rem .6rem / calc(100% - 1.2rem) 1.8rem no-repeat, repeating-linear-gradient(90deg, transparent 0 .3rem, #475569 .3rem 1.4rem, transparent 1.4rem 1.5rem) .6rem 3rem / calc(100% - 1.2rem) 4.4rem no-repeat, repeating-linear-gradient(transparent 0 .2rem, #1f2937 .2rem .45rem, transparent .45rem 1.1rem) .6rem 3rem / calc(100% - 1.2rem) 4.4rem no-repeat'],
                ['cyl', 'r' => 1.6, 'h' => .35, 'n' => 18, 'c' => 'g', 't' => 'translate3d(4.4rem, 3rem, 1rem)'],
                ['cyl', 'r' => 1.6, 'h' => .35, 'n' => 18, 'c' => 'gl', 't' => 'translate3d(4.5rem, 2.6rem, 1rem)'],
                ['cyl', 'r' => 1.6, 'h' => .35, 'n' => 18, 'c' => 'g', 't' => 'translate3d(4.4rem, 2.2rem, 1rem)'],
                ['cyl', 'r' => 1.6, 'h' => .35, 'n' => 18, 'c' => 'gl', 't' => 'translate3d(4.6rem, 1.8rem, 1rem)'],
            ]],
            'chart' => ['label' => 'رسم بياني طالع', 'tilt' => [-18, -30], 'parts' => [
                ['plane', 'w' => 11, 'h' => 5, 'c' => 'color-mix(in srgb, var(--site-surface) 80%, var(--site-text))', 'rad' => '.6rem', 't' => 'translateY(3.4rem) rotateX(90deg)'],
                ['box', 'w' => 1.6, 'h' => 2.4, 'd' => 1.6, 'c' => 'pll', 't' => 'translate(-3.6rem, 2.2rem)'],
                ['box', 'w' => 1.6, 'h' => 3.8, 'd' => 1.6, 'c' => 'pl', 't' => 'translate(-1.2rem, 1.5rem)'],
                ['box', 'w' => 1.6, 'h' => 5.4, 'd' => 1.6, 'c' => 'p', 't' => 'translate(1.2rem, .7rem)'],
                ['box', 'w' => 1.6, 'h' => 7, 'd' => 1.6, 'c' => 'pd', 't' => 'translate(3.6rem, -.1rem)'],
                ['shape', 'w' => 3, 'h' => 3, 'c' => 'green', 'clip' => 'polygon(50% 0, 100% 50%, 65% 50%, 65% 100%, 35% 100%, 35% 50%, 0 50%)', 'layers' => 4, 't' => 'translate3d(3.6rem, -5.4rem, 0)'],
            ]],
            'stamp' => ['label' => 'ورقة وختم', 'tilt' => [-18, -24], 'parts' => [
                ['plane', 'w' => 7, 'h' => 9, 'c' => 'repeating-linear-gradient(transparent 0 1rem, #cbd5e1 1rem 1.1rem) 0 1.2rem / 70% 60% no-repeat, #fff', 't' => 'translate3d(-1rem, 3.2rem, 0) rotateX(90deg) rotateZ(8deg)'],
                ['plane', 'w' => 2.4, 'h' => 2.4, 'rad' => '50%', 'c' => 'radial-gradient(circle, transparent 0 55%, var(--site-primary) 56% 66%, transparent 67%)', 't' => 'translate3d(.6rem, 3.15rem, 2rem) rotateX(90deg)'],
                ['cyl', 'r' => 1.8, 'h' => .8, 'n' => 16, 'c' => 'p', 't' => 'translate3d(2.6rem, 0, -1rem)'],
                ['cyl', 'r' => .5, 'h' => 2.6, 'n' => 10, 'c' => 'wood', 't' => 'translate3d(2.6rem, -1.7rem, -1rem)'],
                ['ball', 's' => 1.8, 'c' => 'wood', 't' => 'translate3d(2.6rem, -3.4rem, -1rem)'],
            ]],

            // ================= مقاولات وديكور =================
            'sofa' => ['label' => 'كنبة', 'tilt' => [-14, -30], 'parts' => [
                ['box', 'w' => 10, 'h' => 1.8, 'd' => 4.2, 'c' => 'p', 't' => 'translateY(1.4rem)'],
                ['box', 'w' => 10, 'h' => 3.2, 'd' => 1.2, 'c' => 'pd', 't' => 'translate3d(0, -.9rem, -1.6rem)'],
                ['box', 'w' => 1.2, 'h' => 2.8, 'd' => 4.2, 'c' => 'pd', 't' => 'translate(-5.4rem, .5rem)'],
                ['box', 'w' => 1.2, 'h' => 2.8, 'd' => 4.2, 'c' => 'pd', 't' => 'translate(5.4rem, .5rem)'],
                ['box', 'w' => 2.4, 'h' => 2.4, 'd' => .8, 'c' => 'pll', 't' => 'translate3d(-2.6rem, -.6rem, -.6rem) rotateZ(10deg)'],
                ['box', 'w' => .4, 'h' => .8, 'd' => .4, 'c' => 'wood', 't' => 'translate3d(-4.6rem, 2.7rem, 1.6rem)'],
                ['box', 'w' => .4, 'h' => .8, 'd' => .4, 'c' => 'wood', 't' => 'translate3d(4.6rem, 2.7rem, 1.6rem)'],
            ]],
            'kitchen' => ['label' => 'مطبخ', 'tilt' => [-14, -30], 'parts' => [
                ['box', 'w' => 10, 'h' => 4, 'd' => 4, 'c' => 'woodl', 'front' => 'linear-gradient(90deg, transparent 33%, rgb(0 0 0 / .18) 33% 33.6%, transparent 33.6% 66%, rgb(0 0 0 / .18) 66% 66.6%, transparent 66.6%)', 't' => 'translateY(2rem)'],
                ['box', 'w' => 10.4, 'h' => .45, 'd' => 4.4, 'c' => '#e5e7eb', 't' => 'translateY(-.2rem)'],
                ['box', 'w' => 10, 'h' => 2.6, 'd' => 2.4, 'c' => 'p', 't' => 'translate3d(0, -4.8rem, -1rem)'],
                ['box', 'w' => .3, 'h' => 1.6, 'd' => .3, 'c' => 'm', 't' => 'translate3d(2rem, -1.2rem, -1rem)'],
                ['box', 'w' => .3, 'h' => .3, 'd' => 1.2, 'c' => 'm', 't' => 'translate3d(2rem, -2rem, -.6rem)'],
            ]],
            'paint' => ['label' => 'جردل ورولة دهان', 'tilt' => [-16, -26], 'parts' => [
                ['cyl', 'r' => 2.6, 'h' => 3.8, 'n' => 18, 'c' => 'm', 'cap' => 'p', 't' => 'translate(-2rem, 1.6rem)'],
                ['shape', 'w' => 5.4, 'h' => 3, 'c' => 'md', 'rad' => '50% 50% 0 0', 'hole' => '92%', 'layers' => 2, 't' => 'translate(-2rem, -1.5rem)'],
                ['group', 't' => 'translate3d(3.2rem, -.6rem, 1.2rem) rotateZ(-25deg)', 'parts' => [
                    ['cyl', 'r' => .95, 'h' => 4.2, 'n' => 16, 'c' => 'p', 't' => 'rotateZ(90deg)'],
                    ['box', 'w' => .25, 'h' => 2.2, 'd' => .25, 'c' => 'md', 't' => 'translate(2.3rem, 1.2rem)'],
                    ['box', 'w' => .5, 'h' => 2.6, 'd' => .5, 'c' => 'wood', 't' => 'translate(2.3rem, 3.4rem)'],
                ]],
            ]],
            'saw' => ['label' => 'منشار وخشب', 'tilt' => [-14, -28], 'parts' => [
                ['box', 'w' => 10, 'h' => 1, 'd' => 3, 'c' => 'woodl', 't' => 'translateY(3rem)'],
                ['box', 'w' => 9, 'h' => 1, 'd' => 3, 'c' => 'wood', 't' => 'translate(.4rem, 2rem) rotateY(8deg)'],
                ['box', 'w' => 8, 'h' => 1, 'd' => 3, 'c' => 'woodl', 't' => 'translate(-.2rem, 1rem) rotateY(-6deg)'],
                ['group', 't' => 'translate3d(0, -2.4rem, 1rem) rotateZ(-12deg)', 'parts' => [
                    ['shape', 'w' => 8, 'h' => 2.2, 'c' => 'm', 'clip' => self::zigzag(16), 'layers' => 2, 'front' => 'linear-gradient(180deg, #f1f5f9, #94a3b8)'],
                    ['shape', 'w' => 2.4, 'h' => 2.8, 'c' => 'p', 'rad' => '40%', 'hole' => '42%', 'layers' => 4, 't' => 'translateX(5rem)'],
                ]],
            ]],

            // ================= سيارات =================
            'tire' => ['label' => 'كاوتش', 'tilt' => [-14, -30], 'parts' => [
                ['cyl', 'r' => 3.6, 'h' => 1.8, 'n' => 22, 'c' => 'black', 'cap' => 'radial-gradient(circle, #0b0d10 0 40%, #374151 41% 46%, #1f2937 47%)', 't' => 'translateY(3rem)'],
                ['cyl', 'r' => 3.6, 'h' => 1.8, 'n' => 22, 'c' => 'black', 'cap' => 'radial-gradient(circle, #0b0d10 0 40%, #374151 41% 46%, #1f2937 47%)', 't' => 'translate(.3rem, 1.1rem) rotateY(20deg)'],
                ['group', 't' => 'translate3d(0, -3rem, 0)', 'parts' => [
                    ['group', 'anim' => 'bq3d-spinz-slow', 'parts' => [
                        ['cyl', 'r' => 3.6, 'h' => 1.8, 'n' => 22, 'c' => 'black', 'cap' => 'radial-gradient(circle, #e5e7eb 0 12%, var(--site-primary) 13% 36%, #374151 37% 44%, repeating-conic-gradient(#111318 0 6deg, #2b2f36 6deg 12deg) 45%)', 't' => 'rotateX(90deg)'],
                    ]],
                ]],
            ]],
            'battery' => ['label' => 'بطارية', 'tilt' => [-16, -30], 'parts' => [
                ['box', 'w' => 8, 'h' => 5, 'd' => 4.6, 'c' => 'dark', 'front' => 'linear-gradient(transparent 30%, var(--site-primary) 30% 55%, transparent 55%)', 't' => 'translateY(1rem)'],
                ['box', 'w' => 8.2, 'h' => .6, 'd' => 4.8, 'c' => '#111318', 't' => 'translateY(-1.8rem)'],
                ['cyl', 'r' => .6, 'h' => .9, 'n' => 12, 'c' => 'red', 't' => 'translate3d(-2.6rem, -2.5rem, 0)'],
                ['cyl', 'r' => .6, 'h' => .9, 'n' => 12, 'c' => 'black', 't' => 'translate3d(2.6rem, -2.5rem, 0)'],
                ['shape', 'w' => 3, 'h' => 4.6, 'c' => 'yellow', 'clip' => 'polygon(55% 0, 10% 58%, 45% 58%, 35% 100%, 90% 40%, 55% 40%, 70% 0)', 'layers' => 4, 't' => 'translate3d(0, -5.6rem, 1rem)'],
            ]],
            'oil' => ['label' => 'جركن زيت', 'tilt' => [-14, -30], 'parts' => [
                ['box', 'w' => 5.4, 'h' => 7, 'd' => 2.8, 'c' => 'p', 'front' => 'linear-gradient(transparent 40%, #fff 40% 62%, transparent 62%)'],
                ['shape', 'w' => 3, 'h' => 2.2, 'c' => 'pd', 'rad' => '50% 50% 10% 10%', 'hole' => '50%', 'layers' => 4, 't' => 'translate(.8rem, -4.4rem)'],
                ['cyl', 'r' => .8, 'h' => 1, 'n' => 12, 'c' => 'black', 't' => 'translate(-1.6rem, -4rem)'],
                ['shape', 'w' => 2.4, 'h' => 3.2, 'c' => '#f59e0b', 'clip' => self::DROP, 'layers' => 5, 't' => 'translate3d(4rem, 2.6rem, 1rem)'],
            ]],
            'wash' => ['label' => 'إسفنجة وفقاعات', 'tilt' => [-14, -26], 'floaties' => 'bubbles', 'parts' => [
                ['box', 'w' => 6, 'h' => 3, 'd' => 3.6, 'c' => 'yellow', 'face' => 'radial-gradient(circle at 30% 40%, #ca8a04 0 .2rem, transparent .25rem) 0 0 / 1rem 1rem'],
                ['ball', 's' => 2.6, 'c' => 'rgb(255 255 255 / .6)', 't' => 'translate3d(-2.4rem, -2.6rem, 1rem)'],
                ['ball', 's' => 1.8, 'c' => 'rgb(255 255 255 / .6)', 't' => 'translate3d(.4rem, -3rem, 1.6rem)'],
                ['ball', 's' => 2.2, 'c' => 'rgb(186 230 253 / .7)', 't' => 'translate3d(2.6rem, -2.2rem, .4rem)'],
                ['ball', 's' => 1.4, 'c' => 'rgb(255 255 255 / .6)', 't' => 'translate3d(3.4rem, -4.2rem, 1rem)'],
            ]],
            'car' => ['label' => 'عربية', 'tilt' => [-12, -32], 'parts' => [
                ['box', 'w' => 11, 'h' => 2.2, 'd' => 4.6, 'c' => 'p', 'front' => 'linear-gradient(90deg, #fde68a 0 6%, transparent 6% 94%, #fde68a 94%)', 't' => 'translateY(.6rem)'],
                ['box', 'w' => 5.6, 'h' => 2, 'd' => 4.2, 'c' => 'pd', 'face' => 'linear-gradient(90deg, transparent 8%, #bae6fd 8% 46%, transparent 46% 54%, #bae6fd 54% 92%, transparent 92%)', 't' => 'translate(-.6rem, -1.5rem)'],
                ['cyl', 'r' => 1.2, 'h' => .8, 'n' => 14, 'c' => 'black', 'cap' => 'radial-gradient(circle, #e5e7eb 0 35%, #111318 36%)', 't' => 'translate3d(-3.4rem, 1.9rem, 2.4rem) rotateX(90deg)'],
                ['cyl', 'r' => 1.2, 'h' => .8, 'n' => 14, 'c' => 'black', 'cap' => 'radial-gradient(circle, #e5e7eb 0 35%, #111318 36%)', 't' => 'translate3d(3.4rem, 1.9rem, 2.4rem) rotateX(90deg)'],
                ['cyl', 'r' => 1.2, 'h' => .8, 'n' => 14, 'c' => 'black', 't' => 'translate3d(-3.4rem, 1.9rem, -2.4rem) rotateX(90deg)'],
                ['cyl', 'r' => 1.2, 'h' => .8, 'n' => 14, 'c' => 'black', 't' => 'translate3d(3.4rem, 1.9rem, -2.4rem) rotateX(90deg)'],
            ]],

            // ================= فعاليات =================
            'rings' => ['label' => 'خاتمين', 'floaties' => 'hearts', 'tilt' => [-10, -20], 'parts' => [
                ['shape', 'w' => 5.4, 'h' => 5.4, 'c' => 'g', 'rad' => '50%', 'hole' => '78%', 'layers' => 8, 'step' => .14, 't' => 'translate(-1.6rem, .8rem) rotateY(-30deg)'],
                ['shape', 'w' => 5, 'h' => 5, 'c' => 'gl', 'rad' => '50%', 'hole' => '78%', 'layers' => 8, 'step' => .14, 't' => 'translate(1.6rem, .2rem) rotateY(40deg)'],
                ['shape', 'w' => 2.2, 'h' => 2, 'c' => 'color-mix(in srgb, #bae6fd 70%, #fff)', 'clip' => 'polygon(25% 0, 75% 0, 100% 30%, 50% 100%, 0 30%)', 'layers' => 5, 't' => 'translate3d(1.6rem, -2.9rem, 0) rotateY(40deg)'],
            ]],
            'partyhat' => ['label' => 'طرطور وبلالين', 'tilt' => [-10, -20], 'parts' => [
                ['shape', 'w' => 4.4, 'h' => 6.4, 'c' => 'repeating-linear-gradient(135deg, var(--site-primary) 0 .8rem, #fde68a .8rem 1.6rem)', 'clip' => self::TRI, 'layers' => 10, 'step' => .36, 't' => 'translate(-1.6rem, .6rem) rotateZ(-10deg)'],
                ['ball', 's' => 1.4, 'c' => 'yellow', 't' => 'translate(-2.1rem, -2.8rem)'],
                ['shape', 'w' => 3, 'h' => 3.6, 'c' => '#f43f5e', 'rad' => '50% 50% 48% 48%', 'layers' => 4, 't' => 'translate3d(3rem, -2.4rem, -1rem)'],
                ['shape', 'w' => 2.6, 'h' => 3.2, 'c' => '#38bdf8', 'rad' => '50% 50% 48% 48%', 'layers' => 4, 't' => 'translate3d(4.6rem, -.6rem, 1rem)'],
                ['box', 'w' => .05, 'h' => 4, 'd' => .05, 'c' => 'md', 't' => 'translate3d(3rem, 1.4rem, -1rem)'],
            ]],
            'mic' => ['label' => 'مايك ومنصة', 'tilt' => [-12, -26], 'parts' => [
                ['box', 'w' => 6, 'h' => 5.4, 'd' => 3.4, 'c' => 'pd', 'front' => 'linear-gradient(transparent 15%, var(--site-primary) 15% 30%, transparent 30%)', 't' => 'translateY(3rem)'],
                ['box', 'w' => .3, 'h' => 4, 'd' => .3, 'c' => 'md', 't' => 'translate3d(0, -1.6rem, 0) rotateZ(10deg)'],
                ['cyl', 'r' => .55, 'h' => 2.4, 'n' => 12, 'c' => 'dark', 't' => 'translate3d(.5rem, -4.2rem, 0) rotateZ(10deg)'],
                ['ball', 's' => 2.2, 'c' => 'md', 't' => 'translate3d(.75rem, -5.8rem, 0)'],
            ]],
            'spotlight' => ['label' => 'كشافات وسماعة', 'tilt' => [-10, -24], 'parts' => [
                ['plane', 'w' => 7, 'h' => 10, 'c' => 'linear-gradient(color-mix(in srgb, var(--site-primary) 50%, transparent), transparent)', 't' => 'translate3d(-1.4rem, 3rem, 0) rotateZ(-18deg)'],
                ['cyl', 'r' => 1.8, 'h' => 3.6, 'n' => 16, 'c' => 'dark', 'cap' => 'radial-gradient(circle, #fff 0 30%, var(--site-primary) 50%, #1f2937 72%)', 't' => 'translate3d(-3rem, -3rem, 0) rotateZ(-18deg)'],
                ['box', 'w' => 3.6, 'h' => 5.6, 'd' => 3, 'c' => 'dark', 'front' => 'radial-gradient(circle at 50% 30%, #4b5563 0 .9rem, #111318 .95rem 1.1rem, transparent 1.15rem), radial-gradient(circle at 50% 72%, #4b5563 0 1.3rem, #111318 1.35rem 1.5rem, transparent 1.55rem)', 't' => 'translate3d(3.6rem, 1.6rem, 1rem)'],
            ]],

            // ================= سفر =================
            'jet' => ['label' => 'طيارة', 'anim' => 'float', 'tilt' => [-14, -34], 'parts' => [
                ['cyl', 'r' => 1.2, 'h' => 11, 'n' => 16, 'c' => 'w', 't' => 'rotateZ(90deg)'],
                ['ball', 's' => 2.4, 'c' => 'w', 't' => 'translateX(5.5rem)'],
                ['box', 'w' => 3, 'h' => .25, 'd' => 12, 'c' => 'm', 't' => 'translateX(.4rem)'],
                ['shape', 'w' => 2.6, 'h' => 3, 'c' => 'p', 'clip' => 'polygon(0 100%, 60% 0, 100% 0, 100% 100%)', 'layers' => 2, 't' => 'translate(-4.6rem, -2rem)'],
                ['box', 'w' => 2, 'h' => .2, 'd' => 4.4, 'c' => 'm', 't' => 'translateX(-4.8rem)'],
                ['plane', 'w' => 9, 'h' => .5, 'c' => 'p', 't' => 'translate3d(0, .3rem, 1.22rem)'],
            ]],
            'kaaba' => ['label' => 'الكعبة', 'tilt' => [-14, -30], 'floaties' => 'none', 'parts' => [
                ['plane', 'w' => 12, 'h' => 12, 'rad' => '50%', 'c' => 'radial-gradient(circle, #f8fafc 0 40%, #e2e8f0 41%)', 't' => 'translateY(3.4rem) rotateX(90deg)'],
                ['box', 'w' => 5.6, 'h' => 6.4, 'd' => 5.6, 'c' => '#111318', 'face' => 'linear-gradient(transparent 18%, #c9a13b 18% 26%, transparent 26%)', 'front' => 'linear-gradient(transparent 18%, #c9a13b 18% 26%, transparent 26%), linear-gradient(90deg, transparent 62%, #c9a13b 62% 76%, transparent 76%) 0 60% / 100% 40% no-repeat', 't' => 'translateY(.2rem)'],
            ]],
            'beach' => ['label' => 'شمسية وشاطئ', 'tilt' => [-14, -24], 'parts' => [
                ['plane', 'w' => 13, 'h' => 8, 'rad' => '1rem', 'c' => 'linear-gradient(#38bdf8 0 35%, #e9cf98 35%)', 't' => 'translateY(3.4rem) rotateX(90deg)'],
                ['box', 'w' => .25, 'h' => 6.4, 'd' => .25, 'c' => 'w', 't' => 'translate3d(-1rem, .2rem, 1rem) rotateZ(8deg)'],
                ['cyl', 'r' => 3.6, 'h' => .3, 'n' => 18, 'c' => 'p', 'cap' => 'repeating-conic-gradient(var(--site-primary) 0 30deg, #fff 30deg 60deg)', 't' => 'translate3d(-.6rem, -3rem, 1rem) rotateZ(8deg)'],
                ['ball', 's' => 2, 'c' => 'orange', 't' => 'translate3d(3.6rem, 2.4rem, 2.4rem)'],
            ]],
            'tent' => ['label' => 'خيمة ونار', 'tilt' => [-16, -30], 'parts' => [
                ['plane', 'w' => 13, 'h' => 9, 'rad' => '50%', 'c' => 'radial-gradient(circle, #e9cf98 0 60%, transparent 61%)', 't' => 'translateY(3rem) rotateX(90deg)'],
                ['shape', 'w' => 7, 'h' => 5.6, 'c' => 'p', 'clip' => self::TRI, 'layers' => 14, 'step' => .4, 'front' => 'linear-gradient(90deg, transparent 44%, color-mix(in srgb, var(--site-primary) 50%, #000) 44% 56%, transparent 56%)', 't' => 'translate(-1.6rem, .2rem)'],
                ['box', 'w' => 1.6, 'h' => .35, 'd' => .35, 'c' => 'wood', 't' => 'translate3d(4rem, 2.8rem, 2rem) rotateY(30deg)'],
                ['box', 'w' => 1.6, 'h' => .35, 'd' => .35, 'c' => 'wood', 't' => 'translate3d(4rem, 2.8rem, 2rem) rotateY(-30deg)'],
                ['flame', 't' => 'translate3d(4rem, 1.8rem, 2rem) scale(1.6)'],
            ]],

            // ================= برمجيات =================
            'apps' => ['label' => 'موبايل وأيقونات', 'tilt' => [-8, -26], 'parts' => [
                ['box', 'w' => 5, 'h' => 9.6, 'd' => .55, 'c' => 'dark', 'front' => 'radial-gradient(circle at 25% 20%, #f43f5e 0 .7rem, transparent .75rem), radial-gradient(circle at 75% 20%, #22c55e 0 .7rem, transparent .75rem), radial-gradient(circle at 25% 45%, #3b82f6 0 .7rem, transparent .75rem), radial-gradient(circle at 75% 45%, #f59e0b 0 .7rem, transparent .75rem), linear-gradient(160deg, var(--site-primary), #0f172a)'],
                ['shape', 'w' => 2.4, 'h' => 2.4, 'c' => 'p', 'rad' => '.6rem', 'layers' => 4, 't' => 'translate3d(4.4rem, -2.8rem, 1.6rem)', 'anim' => 'bq3d-bobby'],
                ['shape', 'w' => 2, 'h' => 2, 'c' => '#22c55e', 'rad' => '.5rem', 'layers' => 4, 't' => 'translate3d(-4.2rem, 2rem, 1.2rem)', 'anim' => 'bq3d-floaty'],
            ]],
            'servers' => ['label' => 'سيرفرات وسحابة', 'tilt' => [-14, -30], 'parts' => [
                ['box', 'w' => 6, 'h' => 2, 'd' => 4.4, 'c' => 'dark', 'front' => 'radial-gradient(circle at 12% 50%, #22c55e 0 .22rem, transparent .26rem), radial-gradient(circle at 20% 50%, var(--site-primary) 0 .22rem, transparent .26rem), repeating-linear-gradient(90deg, transparent 0 55%, #374151 55% 57%)', 't' => 'translateY(2.6rem)'],
                ['box', 'w' => 6, 'h' => 2, 'd' => 4.4, 'c' => 'dark', 'front' => 'radial-gradient(circle at 12% 50%, #22c55e 0 .22rem, transparent .26rem), radial-gradient(circle at 20% 50%, #f59e0b 0 .22rem, transparent .26rem), repeating-linear-gradient(90deg, transparent 0 55%, #374151 55% 57%)', 't' => 'translateY(.4rem)'],
                ['box', 'w' => 6, 'h' => 2, 'd' => 4.4, 'c' => 'dark', 'front' => 'radial-gradient(circle at 12% 50%, #22c55e 0 .22rem, transparent .26rem), radial-gradient(circle at 20% 50%, var(--site-primary) 0 .22rem, transparent .26rem), repeating-linear-gradient(90deg, transparent 0 55%, #374151 55% 57%)', 't' => 'translateY(-1.8rem)'],
                ['group', 't' => 'translate3d(1rem, -5.2rem, 1rem)', 'anim' => 'bq3d-bobby', 'parts' => [
                    ['ball', 's' => 2.6, 'c' => 'pll', 't' => 'translateX(-1.4rem)'], ['ball', 's' => 3.4, 'c' => 'pll', 't' => 'translate(.4rem, -.6rem)'], ['ball', 's' => 2.4, 'c' => 'pll', 't' => 'translateX(2rem)'],
                ]],
            ]],
            'padlock' => ['label' => 'قفل أمان', 'tilt' => [-8, -26], 'floaties' => 'sparkles', 'parts' => [
                ['box', 'w' => 6.4, 'h' => 5.2, 'd' => 2.4, 'c' => 'p', 'front' => 'radial-gradient(circle at 50% 40%, #0f172a 0 .6rem, transparent .65rem), linear-gradient(#0f172a, #0f172a) 50% 62% / .5rem 1.4rem no-repeat', 't' => 'translateY(1.6rem)'],
                ['shape', 'w' => 4.6, 'h' => 5, 'c' => 'm', 'rad' => '50% 50% 0 0', 'hole' => '62%', 'layers' => 6, 'step' => .2, 'clip' => 'polygon(0 0, 100% 0, 100% 60%, 0 60%)', 't' => 'translateY(-1.6rem)'],
            ]],
            'neural' => ['label' => 'شبكة ذكاء اصطناعي', 'anim' => 'turn', 'tilt' => [-14, -10], 'floaties' => 'sparkles', 'parts' => array_merge(
                array_map(fn ($a) => ['ball', 's' => 2, 'c' => 'p', 't' => 'rotateY('.$a.'deg) translateZ(4.4rem) rotateY(-'.$a.'deg)'], [0, 60, 120, 180, 240, 300]),
                array_map(fn ($a) => ['ball', 's' => 1.6, 'c' => 'pl', 't' => 'translateY(-3rem) rotateY('.$a.'deg) translateZ(3rem) rotateY(-'.$a.'deg)'], [30, 120, 210, 300]),
                array_map(fn ($a) => ['ball', 's' => 1.6, 'c' => 'pl', 't' => 'translateY(3rem) rotateY('.$a.'deg) translateZ(3rem) rotateY(-'.$a.'deg)'], [0, 90, 180, 270]),
                [['ball', 's' => 3.4, 'c' => 'pd'], ['shape', 'w' => 9.4, 'h' => 9.4, 'c' => 'pl', 'rad' => '50%', 'hole' => '90%', 'layers' => 2, 't' => 'rotateX(90deg)'], ['shape', 'w' => 7.4, 'h' => 7.4, 'c' => 'p', 'rad' => '50%', 'hole' => '88%', 'layers' => 2, 't' => 'rotateX(60deg) rotateY(30deg)']],
            )],

            // ================= بورتفوليو =================
            'pencils' => ['label' => 'أقلام وباليتة ألوان', 'tilt' => [-14, -24], 'parts' => [
                ['plane', 'w' => 8, 'h' => 6, 'rad' => '50% 50% 45% 55% / 60% 60% 40% 40%', 'c' => 'radial-gradient(circle at 25% 35%, #ef4444 0 .6rem, transparent .65rem), radial-gradient(circle at 45% 25%, #facc15 0 .6rem, transparent .65rem), radial-gradient(circle at 65% 30%, #22c55e 0 .6rem, transparent .65rem), radial-gradient(circle at 75% 55%, var(--site-primary) 0 .6rem, transparent .65rem), radial-gradient(circle at 35% 70%, var(--site-surface) 0 .8rem, transparent .85rem), #f5e6c8', 't' => 'translateY(2.6rem) rotateX(80deg)'],
                ['group', 't' => 'translate3d(-1rem, -1.4rem, 0) rotateZ(-25deg)', 'parts' => [['cyl', 'r' => .55, 'h' => 6, 'n' => 6, 'c' => 'yellow'], ['shape', 'w' => 1.1, 'h' => 1.3, 'c' => 'woodl', 'clip' => self::TRI, 'layers' => 3, 'step' => .3, 't' => 'translateY(-3.6rem)']]],
                ['group', 't' => 'translate3d(1.6rem, -1rem, .6rem) rotateZ(18deg)', 'parts' => [['cyl', 'r' => .55, 'h' => 6, 'n' => 6, 'c' => 'p'], ['shape', 'w' => 1.1, 'h' => 1.3, 'c' => 'woodl', 'clip' => self::TRI, 'layers' => 3, 'step' => .3, 't' => 'translateY(-3.6rem)']]],
            ]],
            'brackets' => ['label' => 'أقواس كود', 'anim' => 'float', 'tilt' => [-8, -22], 'parts' => [
                ['shape', 'w' => 3, 'h' => 5.4, 'c' => 'p', 'clip' => 'polygon(60% 0, 100% 0, 40% 50%, 100% 100%, 60% 100%, 0 50%)', 'layers' => 7, 'step' => .2, 't' => 'translateX(-4rem)'],
                ['shape', 'w' => 2.4, 'h' => 6, 'c' => 'pl', 'clip' => 'polygon(70% 0, 100% 0, 30% 100%, 0 100%)', 'layers' => 7, 'step' => .2],
                ['shape', 'w' => 3, 'h' => 5.4, 'c' => 'p', 'clip' => 'polygon(0 0, 40% 0, 100% 50%, 40% 100%, 0 100%, 60% 50%)', 'layers' => 7, 'step' => .2, 't' => 'translateX(4rem)'],
            ]],
            'quill' => ['label' => 'ريشة وحبر', 'tilt' => [-12, -24], 'parts' => [
                ['plane', 'w' => 7, 'h' => 9, 'c' => 'repeating-linear-gradient(transparent 0 1rem, #cbd5e1 1rem 1.08rem) 0 1rem / 80% 80% no-repeat, #fffdf6', 't' => 'translate3d(-1rem, 3.3rem, 0) rotateX(88deg) rotateZ(-6deg)'],
                ['cyl', 'r' => 1.6, 'h' => 2.2, 'n' => 16, 'c' => 'black', 'cap' => '#0b0d10', 't' => 'translate3d(3.4rem, 2.2rem, 1rem)'],
                ['shape', 'w' => 2.6, 'h' => 8, 'c' => 'pl', 'clip' => 'polygon(50% 0, 70% 15%, 80% 40%, 75% 65%, 55% 90%, 50% 100%, 45% 90%, 25% 65%, 20% 40%, 30% 15%)', 'layers' => 3, 'front' => 'linear-gradient(90deg, transparent 48%, rgb(255 255 255 / .8) 48% 52%, transparent 52%)', 't' => 'translate3d(2.6rem, -2.4rem, 1rem) rotateZ(25deg)'],
            ]],

            // ================= أزياء =================
            'suit' => ['label' => 'بدلة وكرافتة', 'anim' => 'float', 'tilt' => [-4, -20], 'parts' => [
                ['shape', 'w' => 8, 'h' => 9, 'c' => '#1e293b', 'clip' => 'polygon(30% 0, 50% 22%, 70% 0, 100% 12%, 94% 100%, 6% 100%, 0 12%)', 'layers' => 6, 'step' => .25],
                ['shape', 'w' => 2.6, 'h' => 3.4, 'c' => 'w', 'clip' => 'polygon(0 0, 100% 0, 50% 100%)', 'layers' => 1, 't' => 'translate3d(0, -3rem, .7rem)'],
                ['shape', 'w' => 1.2, 'h' => 5, 'c' => 'p', 'clip' => 'polygon(30% 0, 70% 0, 62% 12%, 85% 80%, 50% 100%, 15% 80%, 38% 12%)', 'layers' => 2, 't' => 'translate3d(0, -1.4rem, .8rem)'],
            ]],
            'heels' => ['label' => 'كعب وشنطة', 'tilt' => [-12, -26], 'parts' => [
                ['shape', 'w' => 7, 'h' => 4.4, 'c' => 'p', 'clip' => 'polygon(0 55%, 8% 40%, 28% 44%, 58% 70%, 86% 70%, 100% 80%, 100% 100%, 84% 100%, 82% 86%, 28% 86%, 12% 100%, 6% 100%, 6% 66%)', 'layers' => 10, 'step' => .24, 't' => 'translate(-1.6rem, 1.6rem)'],
                ['box', 'w' => 4, 'h' => 3.4, 'd' => 1.8, 'c' => 'pd', 't' => 'translate3d(3.6rem, -1.4rem, -1rem)'],
                ['shape', 'w' => 2.8, 'h' => 2.8, 'c' => 'g', 'rad' => '50% 50% 0 0', 'hole' => '78%', 'clip' => 'polygon(0 0, 100% 0, 100% 50%, 0 50%)', 'layers' => 2, 't' => 'translate3d(3.6rem, -3rem, -1rem)'],
            ]],
            'abaya' => ['label' => 'عباية', 'anim' => 'float', 'tilt' => [-4, -20], 'parts' => [
                ['shape', 'w' => 7.4, 'h' => 10, 'c' => '#111318', 'clip' => 'polygon(36% 0, 64% 0, 72% 10%, 86% 100%, 14% 100%, 28% 10%)', 'layers' => 6, 'step' => .25, 'front' => 'linear-gradient(90deg, transparent 47%, #c9a13b 47% 53%, transparent 53%), linear-gradient(transparent 94%, #c9a13b 94%)'],
                ['shape', 'w' => 3.4, 'h' => 1.2, 'c' => 'md', 'clip' => 'polygon(50% 0, 100% 100%, 0 100%)', 'layers' => 1, 't' => 'translate3d(0, -5.4rem, 0)'],
            ]],
            'kidswear' => ['label' => 'تيشيرت أطفال', 'anim' => 'float', 'tilt' => [-4, -22], 'floaties' => 'balls', 'parts' => [
                ['shape', 'w' => 8, 'h' => 7, 'c' => 'p', 'clip' => 'polygon(25% 0, 40% 0, 50% 10%, 60% 0, 75% 0, 100% 20%, 88% 38%, 78% 30%, 78% 100%, 22% 100%, 22% 30%, 12% 38%, 0 20%)', 'layers' => 6, 'step' => .22, 'front' => 'radial-gradient(circle at 50% 55%, #facc15 0 1rem, transparent 1.05rem)'],
            ]],

            // ================= مخابز =================
            'bread' => ['label' => 'عيش وفينو', 'tilt' => [-16, -26], 'parts' => [
                ['group', 't' => 'rotateZ(-20deg)', 'parts' => [
                    ['cyl', 'r' => 1.1, 'h' => 10, 'n' => 14, 'c' => '#c98a3e', 'caps' => false, 'stripe' => 'repeating-linear-gradient(170deg, #c98a3e 0 1.3rem, #e9b872 1.3rem 1.6rem)'],
                    ['ball', 's' => 2.2, 'c' => '#c98a3e', 't' => 'translateY(-5rem)'], ['ball', 's' => 2.2, 'c' => '#c98a3e', 't' => 'translateY(5rem)'],
                ]],
                ['ball', 's' => 5.4, 'c' => '#d49a52', 't' => 'translate3d(3.2rem, 2.6rem, 1.4rem)'],
            ]],
            'kunafa' => ['label' => 'صينية كنافة', 'tilt' => [-34, -20], 'parts' => [
                ['cyl', 'r' => 5.4, 'h' => .7, 'n' => 26, 'c' => 'm', 't' => 'translateY(1rem)'],
                ['cyl', 'r' => 5, 'h' => 1.1, 'n' => 26, 'c' => '#e08a2e', 'cap' => 'radial-gradient(circle at 30% 40%, #65a30d 0 .3rem, transparent .35rem) 0 0 / 1.6rem 1.6rem, repeating-radial-gradient(circle, #f2a541 0 .2rem, #d97706 .2rem .35rem)', 't' => 'translateY(.2rem)'],
                ['steam', 't' => 'translate(-1rem, -.8rem)'], ['steam', 't' => 'translate(1.6rem, -1rem)', 'delay' => '1.5s'],
            ]],
            'donut' => ['label' => 'دونتس', 'anim' => 'float', 'tilt' => [-30, -20], 'floaties' => 'sparkles', 'parts' => [
                ['shape', 'w' => 8, 'h' => 8, 'c' => '#d49a52', 'rad' => '50%', 'hole' => '32%', 'layers' => 7, 'step' => .3],
                ['shape', 'w' => 7.4, 'h' => 7.4, 'c' => 'p', 'rad' => '50%', 'hole' => '36%', 'layers' => 2, 'step' => .2, 'front' => 'radial-gradient(circle at 30% 30%, #fff 0 .15rem, transparent .2rem) 0 0 / 1.2rem 1.2rem, radial-gradient(circle at 70% 60%, #facc15 0 .15rem, transparent .2rem) 0 0 / 1.4rem 1.4rem', 't' => 'translateZ(1.1rem)'],
            ]],
            'icecream' => ['label' => 'آيس كريم', 'anim' => 'float', 'tilt' => [-6, -20], 'parts' => [
                ['shape', 'w' => 4.2, 'h' => 6.4, 'c' => '#d9a05b', 'clip' => 'polygon(0 0, 100% 0, 50% 100%)', 'layers' => 9, 'step' => .36, 'front' => 'repeating-linear-gradient(45deg, transparent 0 .6rem, #b7793a .6rem .75rem), repeating-linear-gradient(-45deg, transparent 0 .6rem, #b7793a .6rem .75rem)', 't' => 'translateY(2.6rem)'],
                ['ball', 's' => 4.4, 'c' => 'pl', 't' => 'translateY(-1.1rem)'],
                ['ball', 's' => 3.8, 'c' => '#fef3c7', 't' => 'translateY(-3.8rem)'],
                ['ball', 's' => 1, 'c' => 'red', 't' => 'translateY(-6rem)'],
            ]],

            // ================= حضانات =================
            'rattle' => ['label' => 'رضّاعة وخشخيشة', 'tilt' => [-12, -24], 'floaties' => 'hearts', 'parts' => [
                ['cyl', 'r' => 1.8, 'h' => 5.4, 'n' => 18, 'c' => 'rgb(255 255 255 / .7)', 'cap' => '#fff', 't' => 'translate(-2rem, 1.4rem)'],
                ['cyl', 'r' => 1.7, 'h' => 2.6, 'n' => 18, 'c' => 'w', 't' => 'translate(-2rem, 2.8rem)'],
                ['cyl', 'r' => 1.9, 'h' => 1, 'n' => 18, 'c' => 'p', 't' => 'translate(-2rem, -1.8rem)'],
                ['ball', 's' => 1.8, 'c' => '#f5d0a9', 't' => 'translate(-2rem, -3rem)'],
                ['group', 't' => 'translate3d(3rem, 0, 1rem) rotateZ(25deg)', 'parts' => [
                    ['ball', 's' => 3.2, 'c' => 'yellow', 't' => 'translateY(-2.6rem)'],
                    ['box', 'w' => .5, 'h' => 4, 'd' => .5, 'c' => 'pl', 't' => 'translateY(.6rem)'],
                    ['ball', 's' => 1.2, 'c' => 'pl', 't' => 'translateY(2.8rem)'],
                ]],
            ]],
            'abc' => ['label' => 'حروف وأرقام', 'anim' => 'float', 'tilt' => [-8, -24], 'floaties' => 'balls', 'parts' => [
                ['glyph', 'text' => 'أ', 'fs' => 6, 'c' => '#ef4444', 'w' => 5, 'h' => 6, 'layers' => 8, 'step' => .12, 't' => 'translate(-3.6rem, -1rem)'],
                ['glyph', 'text' => 'ب', 'fs' => 6, 'c' => '#3b82f6', 'w' => 5, 'h' => 6, 'layers' => 8, 'step' => .12, 't' => 'translate3d(.4rem, 1.4rem, 1rem)'],
                ['glyph', 'text' => '1', 'fs' => 6, 'c' => 'var(--site-primary)', 'w' => 4, 'h' => 6, 'layers' => 8, 'step' => .12, 't' => 'translate3d(4rem, -1.6rem, -.6rem)'],
            ]],

            // ================= تصوير =================
            'clapper' => ['label' => 'كلاكيت تصوير', 'tilt' => [-10, -26], 'parts' => [
                ['box', 'w' => 8, 'h' => 5.6, 'd' => .6, 'c' => 'dark', 'front' => 'repeating-linear-gradient(transparent 0 1.6rem, #4b5563 1.6rem 1.7rem)', 't' => 'translateY(1.4rem)'],
                ['box', 'w' => 8, 'h' => 1.1, 'd' => .6, 'c' => 'dark', 'face' => 'repeating-linear-gradient(120deg, #fff 0 .8rem, #111318 .8rem 1.6rem)', 't' => 'translateY(-2rem)'],
                ['group', 't' => 'translate(-4rem, -2.6rem)', 'parts' => [['group', 'anim' => 'bq3d-clap', 'style' => 'transform-origin: 0 0;', 'parts' => [
                    ['box', 'w' => 8, 'h' => 1.1, 'd' => .6, 'c' => 'dark', 'face' => 'repeating-linear-gradient(60deg, #fff 0 .8rem, #111318 .8rem 1.6rem)', 't' => 'translate(4rem, -.55rem)'],
                ]]]],
            ]],
            'drone' => ['label' => 'درون', 'anim' => 'float', 'tilt' => [-22, -24], 'scale' => 1.35, 'parts' => [
                ['box', 'w' => 3.4, 'h' => 1.1, 'd' => 3.4, 'c' => 'dark'],
                ['box', 'w' => 11, 'h' => .4, 'd' => .4, 'c' => 'md', 't' => 'rotateY(45deg)'],
                ['box', 'w' => 11, 'h' => .4, 'd' => .4, 'c' => 'md', 't' => 'rotateY(-45deg)'],
                ...array_map(fn ($a) => ['group', 't' => 'rotateY('.$a.'deg) translateX(5.3rem) translateY(-.6rem)', 'parts' => [
                    ['group', 'anim' => 'bq3d-spinz', 'parts' => [['plane', 'w' => 4.2, 'h' => .5, 'rad' => '1rem', 'c' => 'color-mix(in srgb, var(--site-primary) 70%, transparent)', 't' => 'rotateX(90deg)']]],
                    ['cyl', 'r' => .4, 'h' => .6, 'n' => 10, 'c' => 'dark', 't' => 'translateY(.4rem)'],
                ]], [45, 135, 225, 315]),
                ['ball', 's' => 1.1, 'c' => 'black', 't' => 'translate3d(0, .9rem, 1.4rem)'],
            ]],
            'polaroid' => ['label' => 'صور مطبوعة', 'anim' => 'float', 'tilt' => [-8, -20], 'floaties' => 'hearts', 'parts' => [
                ['plane', 'w' => 6, 'h' => 7, 'c' => 'linear-gradient(160deg, #fbbf24, var(--site-primary)) .5rem .5rem / calc(100% - 1rem) 5.2rem no-repeat, #fff', 't' => 'translate3d(-2.2rem, .6rem, -1rem) rotateZ(-12deg)'],
                ['plane', 'w' => 6, 'h' => 7, 'c' => 'linear-gradient(160deg, #38bdf8, #22c55e) .5rem .5rem / calc(100% - 1rem) 5.2rem no-repeat, #fff', 't' => 'translate3d(1.4rem, -.4rem, 0) rotateZ(6deg)'],
                ['plane', 'w' => 6, 'h' => 7, 'c' => 'linear-gradient(160deg, var(--site-primary), #f472b6) .5rem .5rem / calc(100% - 1rem) 5.2rem no-repeat, #fff', 't' => 'translate3d(3.4rem, 2rem, 1.2rem) rotateZ(18deg)'],
            ]],

            // ================= عطور =================
            'incense' => ['label' => 'مبخرة ودخان', 'tilt' => [-12, -24], 'parts' => [
                ['cyl', 'r' => 2.4, 'h' => .7, 'n' => 18, 'c' => 'g', 't' => 'translateY(3.4rem)'],
                ['cyl', 'r' => .9, 'h' => 2.6, 'n' => 14, 'c' => 'g', 't' => 'translateY(1.8rem)'],
                ['cyl', 'r' => 2.8, 'h' => 1.8, 'n' => 18, 'c' => 'g', 'cap' => 'radial-gradient(circle, #f97316 0 20%, #7c2d12 40%, #3b2414 70%)', 't' => 'translateY(-.3rem)'],
                ['steam', 't' => 'translate(-.6rem, -2rem)'], ['steam', 't' => 'translate(.6rem, -2.4rem)', 'delay' => '1.1s'], ['steam', 't' => 'translate(0, -2.2rem)', 'delay' => '2.2s'],
                ['box', 'w' => .4, 'h' => 5, 'd' => .4, 'c' => 'brown', 't' => 'translate3d(4.4rem, 1.4rem, 1rem) rotateZ(12deg)'],
                ['box', 'w' => .4, 'h' => 5, 'd' => .4, 'c' => '#5b3a1e', 't' => 'translate3d(5.2rem, 1.6rem, .6rem) rotateZ(-6deg)'],
            ]],
            'soap' => ['label' => 'صابون طبيعي', 'tilt' => [-16, -28], 'floaties' => 'bubbles', 'parts' => [
                ['box', 'w' => 6, 'h' => 2, 'd' => 4, 'c' => 'pll', 't' => 'translateY(2.4rem)'],
                ['box', 'w' => 5.4, 'h' => 1.8, 'd' => 3.6, 'c' => 'cream', 't' => 'translateY(.5rem) rotateY(12deg)'],
                ['box', 'w' => 4.8, 'h' => 1.7, 'd' => 3.2, 'c' => 'pl', 't' => 'translateY(-1.3rem) rotateY(-8deg)'],
                ['shape', 'w' => 2.4, 'h' => 1.4, 'c' => 'green', 'clip' => 'polygon(0 100%, 30% 20%, 50% 0, 70% 20%, 100% 100%)', 'layers' => 2, 't' => 'translate3d(.4rem, -2.6rem, 0) rotateZ(-20deg)'],
            ]],

            // ================= بيطري =================
            'cat' => ['label' => 'قطة', 'anim' => 'float', 'tilt' => [0, 0], 'floaties' => 'hearts', 'parts' => [
                ['shape', 'w' => 3, 'h' => 3.4, 'c' => '#f59e0b', 'clip' => self::TRI, 'layers' => 4, 't' => 'translate3d(-2.3rem, -3.4rem, -.4rem) rotateZ(-14deg)'],
                ['shape', 'w' => 3, 'h' => 3.4, 'c' => '#f59e0b', 'clip' => self::TRI, 'layers' => 4, 't' => 'translate3d(2.3rem, -3.4rem, -.4rem) rotateZ(14deg)'],
                ['ball', 's' => 7.4, 'c' => '#f59e0b'],
                ['ball', 's' => 1.2, 'c' => '#14532d', 't' => 'translate3d(-1.5rem, -.8rem, .3rem)'],
                ['ball', 's' => 1.2, 'c' => '#14532d', 't' => 'translate3d(1.5rem, -.8rem, .3rem)'],
                ['shape', 'w' => 1, 'h' => .7, 'c' => 'pink', 'clip' => 'polygon(0 0, 100% 0, 50% 100%)', 'layers' => 1, 't' => 'translate3d(0, .6rem, .4rem)'],
                ['plane', 'w' => 4.6, 'h' => .12, 'c' => '#78350f', 't' => 'translate3d(-2rem, 1rem, .5rem) rotateZ(8deg)'],
                ['plane', 'w' => 4.6, 'h' => .12, 'c' => '#78350f', 't' => 'translate3d(2rem, 1rem, .5rem) rotateZ(-8deg)'],
            ]],
            'dog' => ['label' => 'كلب', 'anim' => 'float', 'tilt' => [0, 0], 'floaties' => 'hearts', 'parts' => [
                ['ball', 's' => 7.4, 'c' => '#c08a52'],
                ['shape', 'w' => 2.6, 'h' => 5, 'c' => '#7a4a24', 'rad' => '50%', 'layers' => 3, 't' => 'translate3d(-3.7rem, .4rem, .2rem) rotateZ(14deg)'],
                ['shape', 'w' => 2.6, 'h' => 5, 'c' => '#7a4a24', 'rad' => '50%', 'layers' => 3, 't' => 'translate3d(3.7rem, .4rem, .2rem) rotateZ(-14deg)'],
                ['ball', 's' => 3.4, 'c' => '#f2d7b0', 't' => 'translate3d(0, 1.4rem, .3rem)'],
                ['ball', 's' => 1.3, 'c' => 'black', 't' => 'translate3d(0, .7rem, .4rem)'],
                ['ball', 's' => 1, 'c' => 'black', 't' => 'translate3d(-1.5rem, -1.2rem, .3rem)'],
                ['ball', 's' => 1, 'c' => 'black', 't' => 'translate3d(1.5rem, -1.2rem, .3rem)'],
                ['shape', 'w' => 1.2, 'h' => 1.4, 'c' => '#f43f5e', 'rad' => '0 0 50% 50%', 'layers' => 1, 't' => 'translate3d(0, 2.9rem, .4rem)'],
            ]],
            'bird' => ['label' => 'عصفورة', 'anim' => 'float', 'tilt' => [-4, -20], 'parts' => [
                ['box', 'w' => 9, 'h' => .5, 'd' => .5, 'c' => 'wood', 't' => 'translateY(3.6rem)'],
                ['ball', 's' => 6, 'c' => 'p'],
                ['ball', 's' => 3.6, 'c' => 'pll', 't' => 'translate3d(.6rem, 1rem, .2rem)'],
                ['shape', 'w' => 4, 'h' => 2.6, 'c' => 'pd', 'rad' => '50% 10% 50% 50%', 'layers' => 3, 't' => 'translate3d(-1.2rem, .2rem, 1rem) rotateZ(-20deg)'],
                ['ball', 's' => .9, 'c' => 'black', 't' => 'translate3d(1.4rem, -1.4rem, .3rem)'],
                ['shape', 'w' => 1.4, 'h' => 1, 'c' => 'orange', 'clip' => 'polygon(0 0, 100% 50%, 0 100%)', 'layers' => 3, 't' => 'translate3d(3.3rem, -.8rem, 0)'],
            ]],
            'petbowl' => ['label' => 'طبق أكل حيوانات', 'tilt' => [-26, -20], 'parts' => [
                ['cyl', 'r' => 3.8, 'h' => 1.8, 'n' => 22, 'c' => 'p', 'cap' => 'radial-gradient(circle at 30% 40%, #92400e 0 .45rem, transparent .5rem) 0 0 / 1.3rem 1.3rem, #b45309', 't' => 'translateY(1.8rem)'],
                ['group', 't' => 'translate3d(2.6rem, -2.2rem, -1rem) rotateZ(-30deg)', 'parts' => [
                    ['box', 'w' => 4, 'h' => 1, 'd' => 1, 'c' => '#f3ead8'],
                    ['ball', 's' => 1.6, 'c' => '#f3ead8', 't' => 'translate(-2.1rem, -.5rem)'], ['ball', 's' => 1.6, 'c' => '#f3ead8', 't' => 'translate(-2.1rem, .5rem)'],
                    ['ball', 's' => 1.6, 'c' => '#f3ead8', 't' => 'translate(2.1rem, -.5rem)'], ['ball', 's' => 1.6, 'c' => '#f3ead8', 't' => 'translate(2.1rem, .5rem)'],
                ]],
            ]],
        ];
    }
}
