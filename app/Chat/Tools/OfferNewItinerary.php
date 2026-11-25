<?php

namespace App\Chat\Tools;

use App\Chat\ChatContext;

class OfferNewItinerary extends BaseTool
{
    public function name(): string
    {
        return 'offer_new_itinerary';
    }

    public function description(): string
    {
        return 'Offer to build a whole new itinerary, shown as a confirm button, when the traveler asks you to plan, '
            . 'create or redo a trip ("make me an itinerary with easier hikes", "plan 3 days with less driving"). The '
            . 'planner builds it from their current trip\'s dates and preferences, nudged by their words (easier, harder, '
            . 'quieter, less driving, more adventurous). Ends your turn; for small changes to an existing trip use '
            . 'edit_itinerary instead.';
    }

    public function parameters(): array
    {
        return self::object([
            'request' => ['type' => 'string', 'description' => 'The traveler\'s request in their words.'],
        ], ['request']);
    }

    public function handle(array $args, ChatContext $context): ToolResult
    {
        $args = $this->validate($args, ['request' => 'required|string|max:500']);
        return new ToolResult(
            ['shown' => true, 'note' => 'A confirm button is on screen. End your turn with one sentence saying what the new trip will change.'],
            [],
            'buttons/VerifySpecializedItinerary',
            ['query' => $args['request'], 'replaceItinerary' => (bool) $context->itinerary],
            endsTurn: true,
        );
    }
}
