import axios from "axios";

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

export class VideoApiUrls {
  public constructor(private apiServer: string) {}

  public listVideos(token: string): string {
    return `${this.apiServer}/videos?token=${token}`;
  }

  public uploadVideo(): string {
    return `${this.apiServer}/videos/create`;
  }

  public deleteVideo(video: Video): string {
    return `${this.apiServer}/videos/delete/${video.key}`;
  }
}

export class VideoApi {
  public constructor(public readonly urls: VideoApiUrls) {}

  public async listVideos(token: string): Promise<Video[]> {
    const response = await axios.get(this.urls.listVideos(token));
    const videos: Video[] = [];
    for (const videoData of response.data['data']['videos']) {
      videos.push(Video.parseFromJson(videoData));
    }
    return videos;
  }

  public async deleteVideo(token: string, video: Video): Promise<void> {
    const formData = new FormData();
    formData.append('token', token);

    await axios.post(this.urls.deleteVideo(video), formData);
  }
}
