import asyncio
import logging
from aiosmtpd.controller import Controller
from aiosmtpd.handlers import Sink

class DebugHandler(Sink):
    async def handle_DATA(self, server, session, envelope):
        print('Message from:', envelope.mail_from)
        print('Message to:', envelope.rcpt_tos)
        print('Message data:')
        print(envelope.content.decode('utf8', errors='replace'))
        print('End of message')
        return '250 Message accepted for delivery'

if __name__ == '__main__':
    logging.basicConfig(level=logging.DEBUG)
    controller = Controller(DebugHandler(), hostname='0.0.0.0', port=25)
    controller.start()
    print(f'SMTP debugging server running on port 25. Press Ctrl+C to stop.')
    try:
        asyncio.run(asyncio.sleep(float('inf')))
    except KeyboardInterrupt:
        controller.stop()
        print('SMTP debugging server stopped.') 