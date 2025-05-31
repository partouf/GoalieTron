'use strict';

var GoalieTron = {
    GetCampaign: function() {
        for (var idx in PatreonData.included) {
            var linkedData = PatreonData.included[idx];
            if (linkedData.type == "campaign")
            {
                return linkedData.attributes;
            }
        }

        return null;
    },
    GetPatronCount: function(campaignData) {
        return campaignData.patron_count;
    },
    GetPledgeSum: function(campaignData) {
        return campaignData.pledge_sum / 100.0;
    },
    GetGoalTotal: function(goalData) {
        return goalData.amount_cents / 100.0;
    },
    GetActiveGoal: function() {
        var campaign = this.GetCampaign();
        var pledged_cents = this.GetPledgeSum(campaign) * 100;
        var lastGoal = null;
        for (var idx in PatreonData.included) {
            var linkedData = PatreonData.included[idx];
            if (linkedData.type == "goal")
            {
                if ((lastGoal != null) && (pledged_cents > lastGoal.amount_cents) && (pledged_cents < linkedData.attributes.amount_cents))
                {
                    lastGoal = linkedData.attributes;
                    break;
                }
                else if ((lastGoal != null) && (pledged_cents < linkedData.attributes.amount_cents))
                {
                    break;
                }
                else
                {
                    lastGoal = linkedData.attributes;
                }
            }
        }

        return lastGoal;
    },
    ShowGoalProgress: function(perc) {
        var percWidth = perc > 100 ? 100 : perc;

        jQuery("#goalietron_meter > span").each(function() {
            jQuery(this)
                .data("origWidth", percWidth + "%")
                .width(0)
                .animate({
                    width: jQuery(this).data("origWidth")
                }, 1200);
        });
    },
    GoalTextFromTo: function(campaignData, goalData) {
        var pledgeSum = 0;
        var goalTotal = 0;

        if (campaignData)
        {
            pledgeSum = Math.floor(this.GetPledgeSum(campaignData));

            if (campaignData.pay_per_name) {
                jQuery("#goalietron_paypername").html("per " + campaignData.pay_per_name);
            } else {
                jQuery("#goalietron_paypername").html("");
            }
        }

        if (goalData)
        {
            goalTotal = Math.floor(this.GetGoalTotal(goalData));

            if (GoalieTronShowGoalText)
            {
                jQuery("#goalietron_goaltext").html(goalData.description);
            }
        }

        // Check if this is a count-based goal (patrons, members, posts) vs income goal
        var isCountGoal = goalData && goalData.goal_type && 
                         (goalData.goal_type === 'patrons' || goalData.goal_type === 'members' || goalData.goal_type === 'posts');
        
        if (isCountGoal) {
            // For count-based goals, show counts without currency
            var currentCount = 0;
            if (goalData.goal_type === 'patrons') {
                currentCount = campaignData.patron_count || 0;
            } else if (goalData.goal_type === 'members') {
                currentCount = campaignData.paid_member_count || 0;
            } else if (goalData.goal_type === 'posts') {
                currentCount = campaignData.creation_count || 0;
            }
            
            var targetCount = goalTotal; // goalTotal is already the correct target count
            
            if (currentCount < targetCount) {
                jQuery("#goalietron_goalmoneytext").html(currentCount.toLocaleString() + " of " + targetCount.toLocaleString());
                jQuery("#goalietron_goalreached").html("");
            } else {
                jQuery("#goalietron_goalmoneytext").html(targetCount.toLocaleString());
                jQuery("#goalietron_goalreached").html("- reached!");
            }
        } else {
            // For income goals, show currency
            if (pledgeSum < goalTotal) {
                jQuery("#goalietron_goalmoneytext").html("$" + pledgeSum + " of $" + goalTotal);
                jQuery("#goalietron_goalreached").html("");
            } else {
                jQuery("#goalietron_goalmoneytext").html("$" + goalTotal);
                jQuery("#goalietron_goalreached").html("- reached!");
            }
        }
    },
    CreateDummyGoal: function(campaignData) {
        return {
            "description": "",
            "amount_cents": campaignData.pledge_sum
        };
    }
};

jQuery(document).ready(function() {
    if (typeof PatreonData['data'] == "object")
    {
        var campaignData = GoalieTron.GetCampaign();
        var goalData = GoalieTron.GetActiveGoal();
        if (!goalData)
        {
            goalData = GoalieTron.CreateDummyGoal(campaignData);
        }

        // Calculate percentage differently for count-based vs income goals
        var goalperc;
        var isCountGoal = goalData && goalData.goal_type && 
                         (goalData.goal_type === 'patrons' || goalData.goal_type === 'members' || goalData.goal_type === 'posts');
        
        if (isCountGoal) {
            // For count goals, use actual counts for percentage calculation
            var currentCount = 0;
            var targetCount = Math.floor(goalData.amount_cents / 100);
            
            if (goalData.goal_type === 'patrons') {
                currentCount = campaignData.patron_count || 0;
            } else if (goalData.goal_type === 'members') {
                currentCount = campaignData.paid_member_count || 0;
            } else if (goalData.goal_type === 'posts') {
                currentCount = campaignData.creation_count || 0;
            }
            
            goalperc = Math.floor((currentCount / targetCount) * 100.0);
        } else {
            // For income goals, use pledge_sum vs amount_cents
            goalperc = Math.floor((campaignData.pledge_sum / goalData.amount_cents) * 100.0);
        }
        jQuery("#goalietron_percentage").val(goalperc);
        GoalieTron.GoalTextFromTo(campaignData, goalData);
        GoalieTron.ShowGoalProgress(goalperc)
    }
});
