'use strict';

var Patreonic = {
    GetCampaign: function() {
        for (var idx in PatreonData.linked) {
            var linkedData = PatreonData.linked[idx];
            if (linkedData.type == "campaign")
            {
                return linkedData;
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
        for (var idx in PatreonData.linked) {
            var linkedData = PatreonData.linked[idx];
            if (linkedData.type == "goal")
            {
                if ((lastGoal != null) && (pledged_cents > lastGoal.amount_cents) && (pledged_cents < linkedData.amount_cents))
                {
                    lastGoal = linkedData;
                    break;
                }
                else if ((lastGoal != null) && (pledged_cents < linkedData.amount_cents))
                {
                    break;
                }
                else
                {
                    lastGoal = linkedData;
                }
            }
        }

        return lastGoal;
    },
    ShowGoalProgress: function(perc) {
        jQuery("#patreonic_meter > span").each(function() {
            jQuery(this)
                .data("origWidth", perc + "%")
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

            jQuery("#patreonic_paypername").html("per " + campaignData.pay_per_name);
        }

        if (goalData)
        {
            goalTotal = Math.floor(this.GetGoalTotal(goalData));

            if (PatreonicShowGoalText)
            {
                jQuery("#patreonic_goaltext").html(goalData.description);
            }
        }

        if (pledgeSum < goalTotal)
        {
            jQuery("#patreonic_goalmoneytext").html("$" + pledgeSum + " of $" + goalTotal);
        }
        else
        {
            jQuery("#patreonic_goalmoneytext").html("$" + goalTotal + " <span class='goalreached'>- reached!</span>");
        }
    }
};

jQuery(document).ready(function() {
    if (typeof PatreonData['data'] == "object")
    {
        var campaignData = Patreonic.GetCampaign();
        var goalData = Patreonic.GetActiveGoal();

        var goalperc = Math.floor((campaignData.pledge_sum / goalData.amount_cents) * 100.0);

        jQuery("#patreonic_percentage").val(goalperc);

        Patreonic.GoalTextFromTo(campaignData, goalData);

        Patreonic.ShowGoalProgress(goalperc)
    }
});
