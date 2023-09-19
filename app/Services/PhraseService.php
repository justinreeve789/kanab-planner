<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Restaurant;
use App\Models\Trail;
use App\Models\Image;
use App\Repositories\ResourceRepository;

class PhraseService
{
    public function getPhraseForActivityResource($resource, $activity = null): string
    {
        if (get_class($resource) == 'App\\Models\\Restaurant') {
            return $this->getPhraseForDining($resource);
        } else if (get_class($resource) == 'App\\Models\\Trail') {
            if ($activity === null) {
                $activity = 'hiking';
            }
            if ($activity === 'hiking') {
                return $this->getPhraseForHiking($resource);
            }
        }
        return '';
    }

    public function getPhraseForArrivingLateFood()
    {
        $phrases = [
            "Consider stopping by the grocery store if it's open on your way.",
            "If you're arriving late, grabbing some fast food might be a convenient option.",
            "Stock up on essentials at the grocery store if it's still open when you arrive.",
            "Late arrival? Fast food could be a time-saving choice for dinner.",
            "Take a moment to assess if a visit to the grocery store is necessary.",
            "Running low on essentials? The grocery store might be open for a quick stop.",
            "Don't forget to check if the grocery store is still open for any last-minute needs.",
            "Late arrival? Fast food restaurants are often open for dinner options.",
            "Before you arrive, think about stopping at a nearby grocery store if necessary.",
            "If you're low on groceries, a visit to the store can be a practical choice.",
            "Late arrival? Fast food can provide a convenient and quick meal.",
            "Consider a visit to the grocery store if you need to replenish supplies.",
            "Arriving late? Fast food outlets often offer convenient dinner choices.",
            "Make sure to confirm if the grocery store is open for any last-minute necessities.",
            "Late arrival? Fast food can provide a straightforward and quick meal solution.",
            "Low on groceries? A quick stop at the store can be a pragmatic choice.",
            "Consider stopping by the grocery store if you need to replenish any supplies.",
            "If you're running low on essentials, assess the grocery store's hours.",
            "Arriving late? Fast food restaurants are often open for convenient dinner choices.",
            "Check whether the grocery store is open for any last-minute shopping needs.",
            "Before you arrive, think about whether a visit to the grocery store is necessary.",
            "If you anticipate needing anything, assess whether a grocery store visit is warranted.",
            "Late arrival? Fast food can provide a straightforward and quick meal solution.",
            "Think about a potential visit to a nearby grocery store before your arrival.",
            "Consider stopping by the grocery store if you need to replenish any supplies."
        ];
        return $phrases[array_rand($phrases)];
    }

    public function getPhraseForBreakfast()
    {
        $phrases = [
            "Make your own breakfast today.",
            "Start your day by preparing your breakfast.",
            "Breakfast is better when you make it yourself.",
            "Create a simple and satisfying morning meal.",
            "Whip up a quick and nutritious breakfast.",
            "Enjoy a homemade breakfast you prepare yourself.",
            "Satisfy your morning hunger with a self-made meal.",
            "Why not cook your breakfast for a tasty start?",
            "Craft a satisfying breakfast at home.",
            "Indulge in a homemade morning meal.",
            "Breakfast is the perfect opportunity to get creative in the kitchen.",
            "Create a nutritious and enjoyable breakfast.",
            "Homemade breakfasts are a great way to kickstart your day.",
            "Satisfy your appetite with a self-prepared breakfast.",
            "Why not make your breakfast a homemade delight?",
            "Start your day by preparing your own breakfast.",
            "Take pride in making your breakfast today.",
            "Cook up a simple and delicious breakfast.",
            "Breakfast is calling, and it's time to cook.",
            "Prepare a homemade breakfast that suits your cravings.",
            "Morning mealtime is your chance to shine in the kitchen.",
            "Craft a quick and satisfying morning dish for yourself.",
            "Make your breakfast memorable with a homemade meal.",
            "Breakfast is more enjoyable when you're the chef.",
            "Prepare a breakfast that's both delicious and budget-friendly.",
            "Enjoy the flavors of a homemade breakfast.",
            "Satisfy your morning hunger with a homemade meal.",
            "Cooking your breakfast can be a rewarding start to the day.",
            "Homemade breakfasts offer a personal touch to your morning.",
            "Craft a morning meal that suits your unique tastes.",
            "Take control of your breakfast by making it yourself.",
            "Prepare a homemade meal and savor every bite.",
            "Breakfast is the perfect opportunity to cook at home.",
            "Create a satisfying and personalized breakfast for yourself.",
            "Whip up a quick and delicious morning meal to enjoy.",
            "Breakfast is better when you're the chef.",
            "Start your day by enjoying a homemade breakfast made just the way you like it.",
            "Satisfy your morning cravings with a self-made meal.",
            "Treat yourself to a homemade breakfast today.",
            "Prepare a flavorful morning meal to kickstart your day.",
            "Indulge in a light homemade breakfast to refuel.",
            "Savor the flavors of a simple, self-made breakfast.",
            "Stay energized with a convenient homemade morning choice.",
            "Treat yourself to a quick, homemade morning delight.",
            "Make a satisfying breakfast that's easy to prepare.",
            "Take a culinary break and cook a simple morning meal.",
            "Craft a breakfast that's both quick and delicious."
        ];
        return $phrases[array_rand($phrases)];
    }

    public function getPhraseForCheckingIn()
    {
        $phrases = [
            "Check-in and settle into your cozy retreat.",
            "Arrive at your home away from home.",
            "Get comfortable in your new surroundings.",
            "Begin your stay by checking into your accommodation.",
            "Unwind as you arrive at your accommodation.",
            "Start your stay by settling into your lodging.",
            "Arrive at your lodgings and make yourself at home.",
            "Check-in and prepare to relax in your accommodation.",
            "Kick off your stay by getting situated in your room.",
            "Begin your journey by checking into your accommodation.",
            "Get settled in your home for the duration of your trip.",
            "Start your adventure by arriving at your accommodations.",
            "Make your arrival memorable by checking into your lodging.",
            "Arrive and take a moment to get comfortable.",
            "Begin your stay by settling into your vacation spot.",
            "Get situated and ready to explore your destination.",
            "Start your trip by checking into your accommodation.",
            "Arrive and make your accommodations your own.",
            "Check-in and get ready to enjoy your stay.",
            "Begin your vacation by settling into your room.",
            "Arrive at your lodgings and make yourself at home.",
            "Start your journey by checking into your accommodation.",
            "Get settled in and prepare for a wonderful stay.",
            "Begin your adventure by arriving at your accommodations.",
            "Make your arrival special by checking into your lodging.",
            "Arrive and take a moment to get situated.",
            "Start your stay by settling into your temporary home.",
            "Get situated and relax in your accommodation.",
            "Check-in and make yourself comfortable.",
            "Begin your trip by arriving at your lodging.",
            "Arrive at your destination and get ready to unwind.",
            "Start your stay by checking into your cozy retreat.",
            "Get settled in your accommodations and relax.",
            "Begin your journey by arriving at your lodging.",
            "Make your arrival memorable by checking into your accommodation.",
            "Arrive and take a moment to get comfortable.",
            "Start your adventure by settling into your room.",
            "Get situated and prepare for a fantastic stay.",
            "Begin your vacation by checking into your accommodation.",
            "Arrive and make your accommodations your own.",
            "Check-in and get ready to enjoy your stay.",
            "Start your trip by settling into your lodging.",
            "Arrive at your lodgings and make yourself at home.",
            "Begin your journey by checking into your accommodation.",
            "Get settled in and prepare for a wonderful stay.",
            "Begin your adventure by arriving at your accommodations.",
            "Make your arrival special by checking into your lodging.",
            "Arrive and take a moment to get comfortable.",
            "Start your stay by settling into your temporary home.",
            "Get situated and relax in your accommodation."
        ];
        return $phrases[array_rand($phrases)];
    }

    public function getPhraseForCheckingOut()
    {
        $phrases = [
            "Bid farewell to your cozy retreat as you check out.",
            "It's time to check out and say goodbye to your home away from home.",
            "Complete your stay by checking out of your accommodations.",
            "Depart from your lodging with cherished memories.",
            "Check out and take a piece of your journey with you.",
            "Conclude your adventure by checking out of your accommodations.",
            "Safely check out and embark on your journey.",
            "Depart with a heart full of travel memories.",
            "Make your departure memorable by checking out gracefully.",
            "It's time to say farewell to your temporary home.",
            "Safely check out and travel back with wonderful memories.",
            "Reluctantly check out, but leave with unforgettable experiences.",
            "Pack your bags and check out to continue your journey.",
            "Leave your accommodations behind, but carry the memories with you.",
            "Check out and travel onward with a smile.",
            "Bid adieu to your vacation spot as you check out.",
            "Safely check out and remember the moments you've cherished.",
            "Depart from your lodgings, but keep the adventure alive in your heart.",
            "It's time to check out and savor the memories you've created.",
            "Safely conclude your stay and embark on your next adventure.",
            "Check out and take a piece of your destination with you.",
            "Say goodbye to your cozy retreat with gratitude.",
            "Safely check out and carry the joy of your journey forward.",
            "Depart from your accommodations and cherish the experiences.",
            "Check out and travel onward with a sense of fulfillment.",
            "Bid farewell to your home away from home as you check out.",
            "It's time to check out and treasure the moments you've had.",
            "Hit the road, reminisce on the adventures you had, and travel safely.",
            "Depart from your lodging, but keep the adventure alive in your memories.",
            "Check out and take the spirit of your trip with you.",
            "Safely conclude your stay and embark on your next chapter.",
            "Leave your accommodations with gratitude for the experiences.",
            "Check out and travel onward with a heart full of memories.",
            "Bid adieu to your temporary home as you check out.",
            "Safely check out and remember the places you've explored.",
            "Depart from your vacation spot, but carry its charm with you.",
            "It's time to check out and savor the essence of your journey.",
            "Safely check out and journey forward with newfound wisdom.",
            "Say goodbye to your lodging and cherish the adventures.",
            "Check out and take the magic of your destination with you.",
            "Bid farewell to your accommodations with a sense of contentment.",
            "Safely check out and carry the beauty of your journey within.",
            "Depart from your cozy retreat with a heart full of gratitude.",
            "It's time to check out and reflect on the experiences.",
            "Safely conclude your stay and embrace the journey ahead.",
            "Check out and take the tranquility of your trip with you.",
            "Say goodbye to your home away from home as you check out.",
            "Depart from your accommodations, but carry their warmth with you.",
            "Check out and travel onward with a spirit of adventure.",
            "Safely check out and hold onto the memories that define your journey."
        ];
        return $phrases[array_rand($phrases)];
    }

    public function getPhraseForDining($resource)
    {
        $name = $resource->location->name;
        $phrases = [
            "Enjoy a delightful meal at $name.",
            "Savor a culinary experience at $name.",
            "Indulge in gastronomic delights at $name.",
            "Taste the flavors of [Cuisine Type] cuisine at $name.",
            "Treat your taste buds to an exquisite dining experience at $name.",
            "Dine in style at $name.",
            "Experience the culinary magic of $name.",
            "Delight in a gourmet feast at $name.",
            "Embark on a culinary journey at $name.",
            "Relish a delectable dinner at $name.",
            "Feast on a mouthwatering spread at $name.",
            "Partake in a memorable dining adventure at $name.",
            "Dive into a world of flavors at $name.",
            "Discover the culinary treasures of $name.",
            "Tantalize your taste buds at $name.",
            "Enjoy an evening of fine dining at $name.",
            "Experience the art of gastronomy at $name.",
            "Immerse yourself in a culinary masterpiece at $name.",
            "Share a meal with friends and family at $name.",
            "Delight in a gastronomic rendezvous at $name.",
            "Feast your senses at $name.",
            "Dine like royalty at $name.",
            "Satisfy your cravings at $name.",
            "Explore the culinary wonders of $name.",
            "Luxuriate in a gourmet dining experience at $name.",
            "Treat yourself to a flavorful evening at $name.",
            "Sip and savor at $name.",
            "Enjoy a delicious dinner date at $name.",
            "Whet your appetite with the offerings at $name.",
            "Partake in a culinary adventure at $name.",
            "Satisfy your palate at $name.",
            "Dine with a view at $name.",
            "Relish a memorable meal at $name.",
            "Savor the creations of the chef at $name.",
            "Delight in a delectable dining experience at $name.",
            "Experience the culinary craftsmanship of $name.",
            "Unwind over a sumptuous meal at $name.",
            "Enjoy a gourmet treat at $name.",
            "Discover the flavors of [Cuisine Type] at $name.",
            "Feast your eyes and palate at $name.",
            "Dine in elegance and style at $name.",
            "Pamper your taste buds with a visit to $name.",
            "Taste the essence of [Cuisine Type] cuisine at $name.",
            "Immerse yourself in a world of culinary delights at $name.",
            "Satisfy your culinary desires at $name.",
            "Share a culinary adventure with loved ones at $name.",
            "Experience the epitome of dining excellence at $name.",
            "Sip on fine wine and dine on exquisite dishes at $name.",
            "Relish a night of culinary exploration at $name.",
            "Let your taste buds dance with joy at $name."
        ];
        return $phrases[array_rand($phrases)];
    }

    public function getPhraseForHiking($trail)
    {
        $phrases = [
            "Embark on a scenic trek through the wilderness on the $trail.",
            "Explore the great outdoors on a hiking adventure, like the $trail.",
            "Hit the trails and embrace the beauty of nature on the $trail.",
            "Commence a journey into the heart of the wilderness on the $trail.",
            "Hike through picturesque landscapes and rugged terrain on the $trail trail.",
            "Connect with nature as you venture into the woods on the $trail hike.",
            "Begin a hiking expedition to conquer new heights like the $trail.",
            "Set off on a trailblazing adventure through the wilderness on the $trail.",
            "Take a hike to immerse yourself in natural wonders, such as the $trail.",
            "Trek through forests, valleys, and mountains on the $trail.",
            "Traverse the wilderness and discover hidden gems along the $trail.",
            "Step into nature's embrace on a breathtaking hike like the $trail.",
            "Embark on a nature walk to recharge your spirit on the $trail.",
            "Start your ascent to awe-inspiring viewpoints on the $trail trail.",
            "Begin a hiking odyssey to find serenity in nature, like on the $trail.",
            "Set out on a trail to uncover breathtaking vistas along the $trail.",
            "Wander through the wild and untamed landscapes of the $trail.",
            "Lace up your hiking boots and hit the trails, starting with the $trail.",
            "Journey through pristine wilderness and fresh air on the $trail.",
            "Embark on an outdoor expedition to reconnect with nature on the $trail.",
            "Take the path less traveled for a unique adventure on the $trail.",
            "Explore the untouched beauty of the great outdoors, as seen on the $trail.",
            "Begin your hike to experience the wonders of nature, starting with the $trail.",
            "Set your sights on hiking to magnificent summits along the $trail.",
            "Traverse rocky terrain on an exciting hiking journey like the $trail.",
            "Walk through the heart of nature's wilderness on the $trail.",
            "Hike along winding trails to discover hidden treasures along the $trail.",
            "Commence a journey of discovery amidst nature's splendor on the $trail.",
            "Set off on an expedition to explore untouched beauty like on the $trail.",
            "Venture into the wilderness for an unforgettable hike on the $trail.",
            "Start your hike and breathe in the fresh mountain air on the $trail.",
            "Trek through lush forests and scenic meadows on the $trail trail.",
            "Embark on a hike to find peace in the great outdoors on the $trail.",
            "Begin your ascent to reach the pinnacle of adventure, starting at $trail.",
            "Take a hike to experience the beauty of solitude along the $trail.",
            "Explore the natural world on a captivating hike like the $trail.",
            "Start your journey into the wild with every step on the $trail.",
            "Hike through the tranquility of serene landscapes found on the $trail.",
            "Traverse challenging terrain for an adrenaline rush on the $trail.",
            "Begin your hike with the promise of scenic rewards, especially on $trail.",
            "Set out on a hiking trail for a nature-inspired escape like $trail.",
            "Walk through unspoiled wilderness and rejuvenate on the $trail.",
            "Embark on a hike to embrace the wonders of nature, starting with $trail.",
            "Start your outdoor exploration with enthusiasm on the $trail.",
            "Take to the trails and leave urban life behind on the $trail route.",
            "Trek through the great outdoors for a dose of adventure on the $trail.",
            "Commence your hike to witness nature's grandeur, particularly on $trail.",
            "Begin your ascent to conquer the peaks of exploration, beginning with $trail.",
            "Set off on a journey to reconnect with the Earth, starting with $trail.",
            "Hike with determination, and let nature guide your way on the $trail."
        ];
        return $phrases[array_rand($phrases)];
    }

    public function getPhraseForLightMeal($period = null)
    {
        $phrases = [
            "Take a moment to prepare a quick and satisfying dish.",
            "Whip up something delicious to curb your hunger.",
            "Create a homemade meal that's both easy and tasty.",
            "Enjoy a small, homemade bite to keep you going.",
            "Prepare a flavorful dish to satisfy your appetite.",
            "Indulge in a light homemade option to refuel.",
            "Savor the flavors of a simple, self-made meal.",
            "Stay fueled with a convenient homemade choice.",
            "Treat yourself to a quick, homemade delight.",
            "Make a satisfying dish that's easy to prepare.",
            "Take a culinary break and cook a simple meal.",
            "Cook up something tasty for a quick refueling.",
            "Create a homemade treat that hits the spot.",
            "Relax and enjoy a self-made meal for a break.",
            "Stay energized with a homemade, light option.",
            "Whip up a quick dish to keep you nourished.",
            "Satisfy your appetite with a homemade selection.",
            "Prepare a straightforward meal to keep you going.",
            "Indulge in a homemade dish that's easy to make.",
            "Treat yourself to a self-prepared bite.",
            "Cook a simple dish and recharge for your adventures.",
            "Stay fueled with a homemade option you can prepare.",
            "Create a satisfying meal that's quick and easy.",
            "Take a culinary pause and prepare your own food.",
            "Cook up a homemade treat to refuel your energy.",
            "Enjoy the flavors of a simple, homemade choice.",
            "Prepare a quick meal to satisfy your hunger.",
            "Indulge in a light, homemade option to stay refreshed.",
            "Savor the taste of a homemade dish.",
            "Stay energized with a convenient, homemade selection.",
            "Whip up something delicious for a satisfying break.",
            "Create a self-made treat that hits the spot.",
            "Relax and enjoy a homemade meal between activities.",
            "Prepare a straightforward dish for a quick meal.",
            "Indulge in a homemade delight that's easy to prepare.",
            "Treat yourself to a homemade option that's easy and tasty.",
            "Cook a simple meal and refuel for your adventures.",
            "Stay fueled with a homemade choice that you can make.",
            "Take a culinary break and enjoy your own prepared food.",
            "Prepare a satisfying homemade meal to keep you going.",
            "Indulge in a self-made dish that's both quick and delicious.",
            "Create a homemade treat to recharge for your next activity.",
            "Enjoy the flavors of a simple, self-prepared option.",
            "Satisfy your hunger with a homemade dish you can make.",
            "Stay energized with a convenient, self-made selection.",
            "Whip up something tasty for a satisfying pause.",
            "Make a self-prepared treat that hits the spot.",
            "Relax and enjoy a homemade meal to refuel.",
            "Prepare a straightforward option for a quick bite.",
            "Cook a light meal and recharge your energy."
        ];
        return $phrases[array_rand($phrases)];
    }

    public function getPhraseForLunch()
    {
        $phrases = [
            "Make your own lunch today.",
            "Take a break and prepare your lunch.",
            "Lunchtime is the perfect moment to cook for yourself.",
            "Create a simple and satisfying lunch.",
            "Whip up a quick and delicious meal.",
            "Enjoy a homemade lunch you prepare yourself.",
            "Lunch tastes better when you make it yourself.",
            "Prepare a tasty lunch to fuel your day.",
            "Craft a satisfying lunch at home.",
            "Indulge in a homemade meal for lunch.",
            "Lunchtime is your chance to shine in the kitchen.",
            "Create a nutritious and enjoyable lunch.",
            "Homemade lunches are a great way to go.",
            "Satisfy your hunger with a self-made lunch.",
            "Why not make your lunch a homemade delight?",
            "Lunch is better when you prepare it yourself.",
            "Take pride in making your lunch today.",
            "Cook up a simple and tasty lunch.",
            "Lunchtime is calling, and it's time to cook.",
            "Prepare a homemade lunch that suits your cravings.",
            "Lunchtime is your opportunity to get creative with cooking.",
            "Craft a quick and satisfying meal for yourself.",
            "Make your lunch break memorable with a homemade meal.",
            "Lunch is more enjoyable when you're the chef.",
            "Prepare a lunch that's both delicious and budget-friendly.",
            "Enjoy the flavors of a homemade lunch.",
            "Satisfy your appetite with a homemade meal.",
            "Cooking your lunch can be a rewarding experience.",
            "Homemade lunches offer a personal touch to your day.",
            "Craft a lunch that suits your unique tastes.",
            "Take control of your lunch by making it yourself.",
            "Prepare a homemade meal and savor every bite.",
            "Lunchtime is the perfect opportunity to cook at home.",
            "Create a satisfying and personalized lunch for yourself.",
            "Whip up a quick and delicious lunch to enjoy.",
            "Lunch is better when you're the chef.",
            "Enjoy a homemade lunch made just the way you like it.",
            "Satisfy your midday cravings with a self-made lunch.",
            "Treat yourself to a homemade lunch today.",
            "Prepare a flavorful lunch to satisfy your appetite.",
            "Indulge in a light homemade option to refuel.",
            "Savor the flavors of a simple, self-made lunch.",
            "Stay fueled with a convenient homemade choice.",
            "Treat yourself to a quick, homemade delight.",
            "Make a satisfying lunch that's easy to prepare.",
            "Take a culinary break and cook a simple meal.",
            "Craft a lunch that's both quick and delicious."
        ];
        return $phrases[array_rand($phrases)];
    }
}