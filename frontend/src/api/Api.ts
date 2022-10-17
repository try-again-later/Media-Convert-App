import { AuthApi, AuthApiUrls } from "@api/Auth";
import { VideoApi, VideoApiUrls } from "@api/Video";

export default class Api {
  public readonly auth: AuthApi;
  public readonly video: VideoApi;

  public constructor(apiServer: string) {
    this.auth = new AuthApi(new AuthApiUrls(apiServer));
    this.video = new VideoApi(new VideoApiUrls(apiServer));
  }
}
