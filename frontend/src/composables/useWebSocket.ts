import { ref } from 'vue';
import { Status } from '../components/Retry/RetryComponent';

export function useWebSocket(endpoint: string) {
  const status = ref<Status>('first-try');
  const webSocket = ref<WebSocket>();

  const connect = async () => {
    webSocket.value = await new Promise<WebSocket>((resolve, reject) => {
      const webSocket = new WebSocket(endpoint);

      const webSocketTimeout = setTimeout(() => {
        webSocket.close();
      }, 10_000);

      webSocket.onopen = () => {
        clearTimeout(webSocketTimeout);
        console.log('Established connection through websockets!');
        resolve(webSocket);
      };

      webSocket.onerror = () => {
        clearTimeout(webSocketTimeout);
        console.error('Failed to connect to the websockets server...');
        webSocket.close();
        reject();
      };

      webSocket.onclose = () => {
        clearTimeout(webSocketTimeout);
        console.log('Closed connection to the websockets server...');
        if (status.value == 'first-try' || status.value == 'success') {
          status.value = 'waiting';
        }
        reject();
      };
    });
  };

  return { status, webSocket, connect };
}
