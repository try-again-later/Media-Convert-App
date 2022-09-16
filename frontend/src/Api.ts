import axios from 'axios';
import to from 'await-to-js';

export class Video {
  public constructor(
    public readonly url: string,
    public readonly expiresAt: Date,
    public readonly originalName: string,
    public readonly key: string,
    public readonly thumbnailUrl: string | null = null,
  ) {}

  public static parseFromJson(data: any): Video {
    return new Video(
      data['url'],
      new Date(data['expires_at']),
      data['original_name'],
      data['key'],
      data['thumbnail_url'],
    );
  }

  public static sortByDate(videoA: Video, videoB: Video): number {
    return videoB.expiresAt.getTime() - videoA.expiresAt.getTime();
  }
}

export class Api {
  public constructor(private apiServer: string) {}

  public authUrl(): string {
    return `${this.apiServer}/auth`;
  }

  public async auth(): Promise<string | null> {
    const response = await axios.post(this.authUrl());
    return response.data['data']['token'];
  }

  public videosUrl(token: string): string {
    return `${this.apiServer}/videos?token=${token}`;
  }

  public async videos(token: string): Promise<Video[]> {
    const response = await axios.get(this.videosUrl(token));
    const videos: Video[] = [];
    for (const videoData of response.data['data']['videos']) {
      videos.push(Video.parseFromJson(videoData));
    }
    return videos;
  }

  public authCheckUrl(): string {
    return `${this.apiServer}/auth-check`;
  }

  public async authCheck(token: string): Promise<boolean> {
    const params = new URLSearchParams();
    params.append('token', token);

    const [authCheckError, authCheckResponse] = await to(axios.post(this.authCheckUrl(), params));
    if (authCheckResponse == null) {
      if (axios.isAxiosError(authCheckError) && authCheckError.response?.status == 401) {
        return false;
      } else {
        throw new Error('Failed to check if user is authenticated.');
      }
    }
    return true;
  }

  public uploadUrl(): string {
    return `${this.apiServer}/videos/create`;
  }
}
