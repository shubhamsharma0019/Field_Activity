const http = require('http');

const targetHost = '127.0.0.1';
const targetPort = 8000;
const listenHost = '0.0.0.0';
const listenPort = Number(process.env.LAN_PROXY_PORT || 8001);

const server = http.createServer((clientReq, clientRes) => {
    const options = {
        hostname: targetHost,
        port: targetPort,
        path: clientReq.url,
        method: clientReq.method,
        headers: {
            ...clientReq.headers,
            host: `${targetHost}:${targetPort}`,
        },
    };

    const proxyReq = http.request(options, (proxyRes) => {
        clientRes.writeHead(proxyRes.statusCode || 500, proxyRes.headers);
        proxyRes.pipe(clientRes);
    });

    proxyReq.on('error', (error) => {
        clientRes.writeHead(502, { 'content-type': 'text/plain; charset=utf-8' });
        clientRes.end(`Local proxy could not reach Laravel on ${targetHost}:${targetPort}.\n${error.message}`);
    });

    clientReq.pipe(proxyReq);
});

server.listen(listenPort, listenHost, () => {
    console.log(`LAN proxy listening on http://${listenHost}:${listenPort}`);
    console.log(`Forwarding to http://${targetHost}:${targetPort}`);
});
