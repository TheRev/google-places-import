jQuery(document).ready(function($) {
    // Only initialize if we have the canvas
    if (!$('#gpd-usage-graph').length) {
        return;
    }

    // Get the context
    var ctx = document.getElementById('gpd-usage-graph').getContext('2d');
    
    // Parse the data from the data attribute
    var usageData = JSON.parse($('#gpd-usage-graph').data('usage'));
    
    // Prepare data for the chart
    var labels = Object.keys(usageData);
    var textSearchData = [];
    var placeDetailsData = [];
    var photosData = [];
    var costData = [];
    
    labels.forEach(function(date) {
        textSearchData.push(usageData[date].text_search || 0);
        placeDetailsData.push(usageData[date].place_details || 0);
        photosData.push(usageData[date].photos || 0);
        costData.push(usageData[date].total_cost || 0);
    });
    
    // Create the chart
    var usageChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Text Search',
                    data: textSearchData,
                    borderColor: 'rgb(75, 192, 192)',
                    tension: 0.1
                },
                {
                    label: 'Place Details',
                    data: placeDetailsData,
                    borderColor: 'rgb(255, 99, 132)',
                    tension: 0.1
                },
                {
                    label: 'Photos',
                    data: photosData,
                    borderColor: 'rgb(153, 102, 255)',
                    tension: 0.1
                },
                {
                    label: 'Cost ($)',
                    data: costData,
                    borderColor: 'rgb(255, 159, 64)',
                    tension: 0.1,
                    yAxisID: 'cost'
                }
            ]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    position: 'left',
                    title: {
                        display: true,
                        text: 'Number of Requests'
                    }
                },
                cost: {
                    beginAtZero: true,
                    position: 'right',
                    title: {
                        display: true,
                        text: 'Cost ($)'
                    }
                }
            },
            plugins: {
                title: {
                    display: true,
                    text: 'API Usage Trends'
                }
            }
        }
    });
});
