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
    ShowGoalProgress: function(perc, widgetContainer) {
        var percWidth = perc > 100 ? 100 : perc;

        // Find the progress bar within the specific widget container
        var progressBar = widgetContainer ? widgetContainer.querySelector('.goalietron_meter > span') : document.querySelector('.goalietron_meter > span');
        if (progressBar) {
            // Set initial width to 0
            progressBar.style.width = "0%";
            progressBar.style.transition = "width 1.2s ease-out";
            
            // Trigger reflow to ensure transition works
            progressBar.offsetHeight;
            
            // Animate to target width
            requestAnimationFrame(function() {
                progressBar.style.width = percWidth + "%";
            });
        }
    },
    GoalTextFromTo: function(campaignData, goalData, widgetContainer) {
        var pledgeSum = 0;
        var goalTotal = 0;

        if (campaignData)
        {
            pledgeSum = Math.floor(this.GetPledgeSum(campaignData));

            var payperElement = widgetContainer ? widgetContainer.querySelector('.goalietron_paypername') : document.querySelector('.goalietron_paypername');
            if (payperElement) {
                if (campaignData.pay_per_name) {
                    payperElement.innerHTML = "per " + campaignData.pay_per_name;
                } else {
                    payperElement.innerHTML = "";
                }
            }
        }

        if (goalData)
        {
            goalTotal = Math.floor(this.GetGoalTotal(goalData));

            if (GoalieTronShowGoalText)
            {
                var goalTextElement = widgetContainer ? widgetContainer.querySelector('.goalietron_goaltext') : document.querySelector('.goalietron_goaltext');
                if (goalTextElement) {
                    goalTextElement.innerHTML = goalData.description;
                }
            }
        }

        // Check if this is a count-based goal (patrons, members, posts) vs income goal
        var isCountGoal = goalData && goalData.goal_type && 
                         (goalData.goal_type === 'patrons' || goalData.goal_type === 'members' || goalData.goal_type === 'posts');
        
        var moneyTextElement = widgetContainer ? widgetContainer.querySelector('.goalietron_goalmoneytext') : document.querySelector('.goalietron_goalmoneytext');
        var reachedElement = widgetContainer ? widgetContainer.querySelector('.goalietron_goalreached') : document.querySelector('.goalietron_goalreached');
        
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
                if (moneyTextElement) moneyTextElement.innerHTML = currentCount.toLocaleString() + " of " + targetCount.toLocaleString();
                if (reachedElement) reachedElement.innerHTML = "";
            } else {
                if (moneyTextElement) moneyTextElement.innerHTML = targetCount.toLocaleString();
                if (reachedElement) reachedElement.innerHTML = "- reached!";
            }
        } else {
            // For income goals, show currency
            if (pledgeSum < goalTotal) {
                if (moneyTextElement) moneyTextElement.innerHTML = "$" + pledgeSum + " of $" + goalTotal;
                if (reachedElement) reachedElement.innerHTML = "";
            } else {
                if (moneyTextElement) moneyTextElement.innerHTML = "$" + goalTotal;
                if (reachedElement) reachedElement.innerHTML = "- reached!";
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

// Vanilla JS DOM ready replacement
function ready(fn) {
    if (document.readyState !== 'loading') {
        fn();
    } else {
        document.addEventListener('DOMContentLoaded', fn);
    }
}

ready(function() {
    // Find all GoalieTron script tags and process each one
    var scripts = document.querySelectorAll('script[data-widget-id]');
    
    for (var i = 0; i < scripts.length; i++) {
        var script = scripts[i];
        var widgetId = script.getAttribute('data-widget-id');
        var patreonDataVar = widgetId + '_PatreonData';
        var showGoalTextVar = widgetId + '_ShowGoalText';
        
        // Get the PatreonData for this specific widget
        var PatreonData = window[patreonDataVar];
        var GoalieTronShowGoalText = window[showGoalTextVar];
        
        
        if (typeof PatreonData !== 'undefined' && typeof PatreonData['data'] === "object") {
            processGoalieTronWidget(PatreonData, GoalieTronShowGoalText, widgetId, script);
        }
    }
});

function processGoalieTronWidget(PatreonData, GoalieTronShowGoalText, widgetId, script) {
    // Set the current PatreonData globally for the GoalieTron functions
    window.PatreonData = PatreonData;
    window.GoalieTronShowGoalText = GoalieTronShowGoalText;
    
    var campaignData = GoalieTron.GetCampaign();
    var goalData = GoalieTron.GetActiveGoal();
    if (!goalData) {
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
    
    var percentageElement = widgetContainer ? widgetContainer.querySelector('.goalietron_percentage') : document.querySelector('.goalietron_percentage');
    if (percentageElement) {
        percentageElement.value = goalperc;
    }
    
    // Find the widget container (the element that contains all the goalietron elements)
    var widgetContainer = script.parentNode;
    
    GoalieTron.GoalTextFromTo(campaignData, goalData, widgetContainer);
    GoalieTron.ShowGoalProgress(goalperc, widgetContainer);
}

// Debug functions - call these from Chrome DevTools console
window.GoalieTronDebug = {
    // List all GoalieTron widgets found on the page
    listWidgets: function() {
        var scripts = document.querySelectorAll('script[data-widget-id]');
        console.log('Found ' + scripts.length + ' GoalieTron widgets:');
        
        for (var i = 0; i < scripts.length; i++) {
            var script = scripts[i];
            var widgetId = script.getAttribute('data-widget-id');
            var patreonDataVar = widgetId + '_PatreonData';
            var PatreonData = window[patreonDataVar];
            
            console.log('Widget #' + (i+1) + ':');
            console.log('  Widget ID: ' + widgetId);
            console.log('  Data Variable: ' + patreonDataVar);
            console.log('  Has Data: ' + (typeof PatreonData !== 'undefined'));
            
            if (typeof PatreonData !== 'undefined') {
                console.log('  Data Object:', PatreonData);
            }
        }
    },
    
    // Get detailed info about a specific widget
    inspectWidget: function(widgetNumber) {
        widgetNumber = widgetNumber || 1;
        var scripts = document.querySelectorAll('script[data-widget-id]');
        
        if (widgetNumber > scripts.length) {
            console.log('Widget #' + widgetNumber + ' not found. Only ' + scripts.length + ' widgets exist.');
            return;
        }
        
        var script = scripts[widgetNumber - 1];
        var widgetId = script.getAttribute('data-widget-id');
        var patreonDataVar = widgetId + '_PatreonData';
        var PatreonData = window[patreonDataVar];
        
        console.log('=== GoalieTron Widget #' + widgetNumber + ' Debug Info ===');
        console.log('Widget ID: ' + widgetId);
        console.log('Data Variable: ' + patreonDataVar);
        
        if (typeof PatreonData === 'undefined') {
            console.log('ERROR: PatreonData is undefined!');
            return;
        }
        
        console.log('Raw PatreonData:', PatreonData);
        
        // Set as global for inspection
        window.PatreonData = PatreonData;
        
        // Get campaign data
        var campaignData = GoalieTron.GetCampaign();
        console.log('Campaign Data:', campaignData);
        
        // Get goal data
        var goalData = GoalieTron.GetActiveGoal();
        console.log('Goal Data:', goalData);
        
        if (goalData) {
            console.log('Goal Type: ' + goalData.goal_type);
            console.log('Target: ' + (goalData.amount_cents / 100));
            
            if (campaignData) {
                var currentCount = 0;
                if (goalData.goal_type === 'patrons') {
                    currentCount = campaignData.patron_count || 0;
                } else if (goalData.goal_type === 'members') {
                    currentCount = campaignData.paid_member_count || 0;
                } else if (goalData.goal_type === 'posts') {
                    currentCount = campaignData.creation_count || 0;
                }
                
                console.log('Current Count: ' + currentCount);
                console.log('Progress: ' + currentCount + '/' + (goalData.amount_cents / 100));
                console.log('Percentage: ' + Math.floor((currentCount / (goalData.amount_cents / 100)) * 100) + '%');
            }
        }
        
        // Check DOM elements within this widget's container
        var widgetContainer = script.parentNode;
        var elements = {
            meter: widgetContainer.querySelector('.goalietron_meter'),
            goalText: widgetContainer.querySelector('.goalietron_goaltext'),
            goalMoney: widgetContainer.querySelector('.goalietron_goalmoneytext'),
            topText: widgetContainer.querySelector('.goalietron_toptext'),
            bottomText: widgetContainer.querySelector('.goalietron_bottomtext'),
            payper: widgetContainer.querySelector('.goalietron_paypername'),
            reached: widgetContainer.querySelector('.goalietron_goalreached')
        };
        
        console.log('DOM Elements found in this widget:');
        for (var key in elements) {
            console.log('  ' + key + ': ' + (elements[key] ? 'Found' : 'Missing'));
        }
    },
    
    // Force reprocess a widget
    reprocessWidget: function(widgetNumber) {
        widgetNumber = widgetNumber || 1;
        var scripts = document.querySelectorAll('script[data-widget-id]');
        
        if (widgetNumber > scripts.length) {
            console.log('Widget #' + widgetNumber + ' not found.');
            return;
        }
        
        var script = scripts[widgetNumber - 1];
        var widgetId = script.getAttribute('data-widget-id');
        var patreonDataVar = widgetId + '_PatreonData';
        var showGoalTextVar = widgetId + '_ShowGoalText';
        
        var PatreonData = window[patreonDataVar];
        var ShowGoalText = window[showGoalTextVar];
        
        console.log('Reprocessing widget #' + widgetNumber + ' (ID: ' + widgetId + ')');
        
        if (typeof PatreonData !== 'undefined') {
            processGoalieTronWidget(PatreonData, ShowGoalText, widgetId, script);
            console.log('Widget reprocessed successfully!');
        } else {
            console.log('ERROR: No PatreonData found for this widget!');
        }
    }
};