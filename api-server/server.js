const express = require('express');
const app = express();
const PORT = 3000;

// Middleware to parse URL-encoded and JSON bodies
app.use(express.urlencoded({ extended: true }));
app.use(express.json());

// Log all incoming requests
app.use((req, res, next) => {
    console.log(`[${new Date().toISOString()}] ${req.method} ${req.path}`);
    next();
});

// Main event endpoint
app.post('/events', (req, res) => {
    const eventData = { ...req.query, ...req.body };
    
    console.log('='.repeat(60));
    console.log('EVENT RECEIVED:');
    console.log('  Type:', eventData.event || 'unknown');
    console.log('  Client ID:', eventData.client_id || 'none');
    console.log('  Timestamp:', eventData.timestamp || 'none');
    console.log('  Data:', JSON.stringify(eventData, null, 2));
    console.log('='.repeat(60));
    
    res.status(200).json({ 
        status: 'ok', 
        message: 'Event received',
        event: eventData.event 
    });
});

// Health check endpoint
app.get('/health', (req, res) => {
    res.json({ status: 'ok', uptime: process.uptime() });
});

// Start server
app.listen(PORT, 'localhost', () => {
    console.log('='.repeat(60));
    console.log(`OpenMoHAA Event Tracker API Server`);
    console.log(`Listening on http://localhost:${PORT}`);
    console.log(`Ready to receive events from tracker.scr`);
    console.log('='.repeat(60));
});
