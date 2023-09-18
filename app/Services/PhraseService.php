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
}