<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div style="display: flex; flex-direction: column; align-items: center; gap: 20px; padding: 20px;">
        <canvas id="chart1" width="400" height="200"></canvas>
        <canvas id="chart2" width="400" height="200"></canvas>
        <canvas id="chart3" width="400" height="200"></canvas>
        <canvas id="chart4" width="400" height="200"></canvas>
    </div>

    <script>
        // Sample data for charts
        const data1 = {
            labels: ['January', 'February', 'March', 'April', 'May', 'June'],
            datasets: [{
                label: 'Sales',
                data: [12, 19, 3, 5, 2, 3],
                borderColor: 'rgba(75, 192, 192, 1)',
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                tension: 0.1
            }]
        };

        const data2 = {
            labels: ['January', 'February', 'March', 'April', 'May', 'June'],
            datasets: [{
                label: 'Revenue',
                data: [15, 22, 8, 10, 5, 7],
                borderColor: 'rgba(255, 99, 132, 1)',
                backgroundColor: 'rgba(255, 99, 132, 0.2)',
                tension: 0.1
            }]
        };

        const data3 = {
            labels: ['January', 'February', 'March', 'April', 'May', 'June'],
            datasets: [{
                label: 'Users',
                data: [20, 25, 15, 30, 18, 22],
                borderColor: 'rgba(54, 162, 235, 1)',
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                tension: 0.1
            }]
        };

        const data4 = {
            labels: ['January', 'February', 'March', 'April', 'May', 'June'],
            datasets: [{
                label: 'Profit',
                data: [8, 12, 4, 6, 3, 5],
                borderColor: 'rgba(255, 205, 86, 1)',
                backgroundColor: 'rgba(255, 205, 86, 0.2)',
                tension: 0.1
            }]
        };

        // Initialize charts
        new Chart(document.getElementById('chart1').getContext('2d'), {
            type: 'line',
            data: data1,
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Chart 1: Sales Over Time'
                    }
                }
            }
        });

        new Chart(document.getElementById('chart2').getContext('2d'), {
            type: 'line',
            data: data2,
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Chart 2: Revenue Over Time'
                    }
                }
            }
        });

        new Chart(document.getElementById('chart3').getContext('2d'), {
            type: 'line',
            data: data3,
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Chart 3: Users Over Time'
                    }
                }
            }
        });

        new Chart(document.getElementById('chart4').getContext('2d'), {
            type: 'line',
            data: data4,
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Chart 4: Profit Over Time'
                    }
                }
            }
        });
    </script>
</body>
</html>
