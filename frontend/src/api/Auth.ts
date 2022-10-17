import to from "await-to-js";
import axios from "axios";

export class AuthApiUrls {
  public constructor(private apiServer: string) {}

  public auth(): string {
    return `${this.apiServer}/auth`;
  }

  public authCheck(): string {
    return `${this.apiServer}/auth-check`;
  }
}

export class AuthApi {
  public constructor(public readonly urls: AuthApiUrls) {}

  public async auth(): Promise<string | null> {
    const response = await axios.post(this.urls.auth());
    return response.data['data']['token'];
  }

  public async authCheck(token: string): Promise<boolean> {
    const params = new URLSearchParams();
    params.append('token', token);

    const [authCheckError, authCheckResponse] = await to(axios.post(this.urls.authCheck(), params));
    if (authCheckResponse == null) {
      if (axios.isAxiosError(authCheckError) && authCheckError.response?.status == 401) {
        return false;
      } else {
        throw new Error('Failed to check if user is authenticated.');
      }
    }
    return true;
  }
}
