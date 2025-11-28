const axios = require('axios');

const API_URL = 'http://127.0.0.1:8000/api';
const DB_NAME = 'admin';

async function testSync() {
    console.log('Testing Sync Endpoint with kWh Math...');

    const newSocketId = 'ESP_TEST_MATH_' + Date.now();

    // Test Case 1: 1000W for 1 hour (3600s) -> Should be 1.0 kWh
    try {
        console.log('Sending: 1000W, 3600s (Expected: 1.0 kWh)');
        const response = await axios.post(`${API_URL}/consumption/sync-firebase`, {
            name: DB_NAME,
            load_type: 'light',
            socket_id: newSocketId,
            power: 1000,
            duration_seconds: 3600
        });

        console.log('Response:', JSON.stringify(response.data, null, 2));
    } catch (error) {
        console.error('Request failed:', error.message);
    }

    // Test Case 2: 100W for 60s -> Should be 0.001666... kWh
    try {
        console.log('\nSending: 100W, 60s (Expected: ~0.001667 kWh)');
        const response = await axios.post(`${API_URL}/consumption/sync-firebase`, {
            name: DB_NAME,
            load_type: 'light',
            socket_id: newSocketId,
            power: 100,
            duration_seconds: 60
        });

        console.log('Response:', JSON.stringify(response.data, null, 2));
    } catch (error) {
        console.error('Request failed:', error.message);
    }
}

testSync();
