<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Setting;

class SettingSeeder extends Seeder
{
    public function run()
    {

        Setting::create([
            'key' => 'relationship_type',
            'value' => [
                "Marriage",
                "Friend",
                "Colleague",
            ],
        ]);
        Setting::create([
            'key' => 'subscribe_amount',
            'value' => [
                "1000",
            ],
        ]);
        Setting::create([
            'key' => 'address_verification_amount',
            'value' => [
                "600",
            ],
        ]);

        Setting::create([
            'key' => 'subscribe_day',
            'value' => [
                "7",
            ],
        ]);
        Setting::create([
            'key' => 'height_range',
            'value' => [
                "Under 4'0",
                "4'0 - 4'5",
                "4'6 - 4'11",
                "5'0 - 5'5",
                "5'6 - 5'11",
                "6'0 - 6'5",
                "6'6 - 6'11",
                "7'0 and above"
            ],
        ]);

        Setting::create([
            'key' => 'weight_range',
            'value' => [
                "Under 40kg",
                "40-49kg",
                "50-59kg",
                "60-69kg",
                "70-79kg",
                "80-89kg",
                "90-99kg",
                "100-109kg",
                "110-119kg",
                "120kg and above"
            ],
        ]);

        Setting::create([
            'key' => 'age_range',
            'value' => [
                "18-24",
                "25-29",
                "30-34",
                "35-39",
                "40-44",
                "45-49",
                "50-54",
                "55-59",
                "60+"
            ],
        ]);

        Setting::create([
            'key' => 'ethnicity',
            'value' => ["Yoruba", "Igbo", "Hausa", "Other"],
        ]);

        Setting::create([
            "key" => "marital_status",
            "value" => ["Single", "Married", "Divorced", "Widowed", "Separated"]
        ]);

        Setting::create([
            "key" => "nationality",
            "value" => [
                "Nigeria" => [
                    "nationality" => "Nigerian",
                    "states" => [
                        "Abia",
                        "Adamawa",
                        "Akwa Ibom",
                        "Anambra",
                        "Bauchi",
                        "Bayelsa",
                        "Benue",
                        "Borno",
                        "Cross River",
                        "Delta",
                        "Ebonyi",
                        "Edo",
                        "Ekiti",
                        "Enugu",
                        "Gombe",
                        "Imo",
                        "Jigawa",
                        "Kaduna",
                        "Kano",
                        "Katsina",
                        "Kebbi",
                        "Kogi",
                        "Kwara",
                        "Lagos",
                        "Nasarawa",
                        "Niger",
                        "Ogun",
                        "Ondo",
                        "Osun",
                        "Oyo",
                        "Plateau",
                        "Rivers",
                        "Sokoto",
                        "Taraba",
                        "Yobe",
                        "Zamfara",
                        "Federal Capital Territory (FCT)"
                    ]
                ]
            ]
        ]);

        Setting::create([
            "key" => "hobbies",
            "value" => [
                "Reading",
                "Traveling",
                "Cooking",
                "Swimming",
                "Gaming",
                "Cycling",
                "Dancing",
                "Drawing",
                "Photography",
                "Hiking",
                "Writing",
                "Watching Movies",
                "Listening to Music",
                "Gardening",
                "Singing",
                "Crafting",
                "Fishing",
                "Yoga",
                "Martial Arts",
                "Volunteering",
                "Collecting Antiques",
                "Bird Watching",
                "Board Games",
                "Knitting",
                "Learning Languages",
                "Painting",
                "Playing Musical Instruments",
                "Chess",
                "Running",
                "Meditation",
                "DIY Projects",
                "Blogging",
                "Podcasting",
                "Astronomy",
                "Scuba Diving",
                "Archery",
                "Pottery",
                "Stand-up Comedy",
                "Magic Tricks",
                "Urban Exploration"
            ]
        ]);

        Setting::create([
            "key" => "interests",
            "value" => [
                "Technology",
                "Sports",
                "Health & Fitness",
                "Art & Design",
                "Music",
                "Science",
                "Movies & TV Shows",
                "Travel",
                "Food & Drink",
                "Business & Finance",
                "Photography",
                "History",
                "Literature",
                "Politics",
                "Social Media",
                "Gaming",
                "Education",
                "Psychology",
                "Nature & Environment",
                "Animals & Pets",
                "Culture & Society",
                "Current Affairs",
                "Fashion & Beauty",
                "Self-improvement",
                "Spirituality",
                "Artificial Intelligence",
                "Renewable Energy",
                "Space Exploration",
                "Neuroscience",
                "Blockchain",
                "Sustainable Living",
                "Mental Health Awareness",
                "Human Rights",
                "Entrepreneurship",
                "Architecture",
                "Culinary Arts",
                "Anthropology",
                "Robotics",
                "Virtual Reality",
                "Minimalism"
            ]
        ]);

        Setting::create([
            "key" => "language_spoken",
            "value" => [
                "English",
                "Spanish",
                "French",
                "German",
                "Italian",
                "Mandarin",
                "Hindi",
                "Arabic",
                "Russian",
                "Portuguese",
                "Japanese",
                "Korean",
                "Dutch",
                "Swedish",
                "Turkish",
                "Bengali",
                "Punjabi",
                "Urdu",
                "Swahili",
                "Vietnamese",
                "Greek",
                "Thai",
                "Hebrew",
                "Polish",
                "Romanian",
                "Czech",
                "Hungarian",
                "Finnish",
                "Danish",
                "Norwegian",
                "Filipino",
                "Malay",
                "Indonesian",
                "Persian",
                "Ukrainian",
                "Cantonese",
                "Tamil",
                "Telugu",
                "Gujarati",
                "Marathi",
                "Hausa",
                "Igbo",
                "Yoruba",
                "Zulu",
                "Afrikaans",
                "Albanian",
                "Armenian",
                "Bulgarian",
                "Catalan",
                "Croatian",
                "Estonian",
                "Georgian",
                "Icelandic",
                "Irish",
                "Kazakh",
                "Latvian",
                "Lithuanian",
                "Macedonian",
                "Maltese",
                "Serbian",
                "Slovak",
                "Slovenian",
                "Tagalog",
                "Belarusian",
                "Bosnian"
            ]
        ]);
    }
}
