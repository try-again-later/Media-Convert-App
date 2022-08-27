import axios from 'axios';

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
}

export class Api {
  public constructor(private apiServer: string) {}

  public authUrl(): string {
    return `${this.apiServer}/auth`;
  }

  // returns a token to use for subsequent API requests
  public async auth(): Promise<string | null> {
    try {
      const response = await axios.post(this.authUrl());
      return response.data['token'];
    } catch {
      return null;
    }
  }

  public videosUrl(token: string): string {
    return `${this.apiServer}/videos?token=${token}`;
  }

  public async videos(token: string): Promise<Video[]> {
    try {
      const response = await axios.get(this.videosUrl(token));
      const videos: Video[] = [];
      for (const videoData of response.data.videos) {
        videos.push(Video.parseFromJson(videoData));
      }
      return videos;
    } catch {
      return [];
    }
  }

  public uploadUrl(): string {
    return `${this.apiServer}/upload`;
  }
}
