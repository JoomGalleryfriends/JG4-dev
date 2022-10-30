// Scripts to detect the current active tab in the config.edit view

var callback = function(){

    // detect tabs
    let tabs = []
    let L1_tablist = document.querySelector("#L1-tabset div[role='tablist']");
    tabs.L1 = addTabs(L1_tablist);
    let L2_tablist = document.querySelector("#L2-tabset div[role='tablist']");
    tabs.L2 = addTabs(L2_tablist);
    let L3_tablist = document.querySelector("#L3-tabset div[role='tablist']");
    tabs.L3 = addTabs(L3_tablist);

    // add eventListener to tabs
    for(let key in tabs) {
        if(tabs[key] !== null)
        {
            tabs[key].forEach(element => {
                let name = element.getAttribute("aria-controls")
                element.addEventListener("click", function() {
                    changeActiveTab(key, name);
                });
            });
        }        
    }

}; //end callback

var changeActiveTab = function(level, tabname) {
    let input = document.getElementById("actTab_"+level+"_"+tabname);
    input.value = tabname;
    console.log([level, tabname]);
}

var addTabs = function(tablist) {
    if(tablist !== null) {
        return tablist.querySelectorAll("button[role='tab']");
    }
    else {
        return null;
    }
}

if(document.readyState === "complete" || (document.readyState !== "loading" && !document.documentElement.doScroll))
{
  callback();
} else {
  document.addEventListener("DOMContentLoaded", callback);
}