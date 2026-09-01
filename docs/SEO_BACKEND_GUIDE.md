# CineDot Backend - Hướng dẫn Tích hợp SEO & Sitemap cho Frontend Next.js

Tài liệu này cung cấp toàn bộ kết quả audit và hướng dẫn kỹ thuật dành cho Frontend Team (Next.js) khi triển khai Server-Side Rendering (SSR), Static Site Generation (SSG), Dynamic Metadata và Sitemap (`app/sitemap.ts` / `sitemap.xml`).

---

## 1. Tổng quan Kiến trúc Dữ liệu SEO

| Thực thể | Identifier chính (URL SEO) | Fallback tương thích ngược | Trạng thái Public (Index SEO) | Field lastModified (Sitemap) |
| :--- | :--- | :--- | :--- | :--- |
| **Movie (Phim)** | `slug` (ví dụ: `cai-chet-cua-robin-hood`) | `movie_id` (số nguyên) | `status` thuộc `['now_showing', 'upcoming']` | `updated_at` (ISO 8601) |
| **Cinema (Rạp)** | `slug` (ví dụ: `cinedot-landmark-81`) | `cinema_id` (số nguyên) | `is_active = true` | `updated_at` (ISO 8601) |
| **Showtime (Lịch chiếu)**| Gắn theo Movie + Cinema + Date | Realtime / Không cache | `showtime_start >= now()` | Realtime dynamic query |

---

## 2. Danh sách Endpoint phục vụ SEO

### 2.1. Movie SEO Detail & Metadata

* **Endpoint**: `GET /api/v1/movies/{slug}`
* **Mô tả**: Lấy toàn bộ thông tin chi tiết phim để Next.js render trang `/movies/[slug]` và `generateMetadata()`.
* **Hỗ trợ tương thích ngược**: Nếu truyền `slug` dạng ID số (ví dụ `/movies/1284465`), Backend tự động tìm theo `movie_id`, đảm bảo không bao giờ bị 404 với các link cũ.
* **Fields quan trọng cho SEO**:
  * `title`: Tiêu đề hiển thị (`og:title`, `<title>`).
  * `originalTitle`: Tên phim gốc quốc tế.
  * `overview`: Mô tả nội dung (`meta description`, `og:description`).
  * `posterUrl`, `backdropUrl`: URL ảnh đầy đủ phục vụ `og:image`, `twitter:image`.
  * `releaseDate`: Ngày phát hành (`og:video:release_date` hoặc Schema.org).
  * `runtime`: Thời lượng phim (phút).
  * `genres`: Danh sách thể loại (Schema.org `genre`).
  * `cast`, `crew`: Đạo diễn và diễn viên (Schema.org `actor`, `director`).
  * `updated_at`: Thời gian cập nhật nội dung (cho SEO crawler & cache revalidation).

### 2.2. Cinema SEO Detail & Metadata

* **Endpoint**: `GET /api/v1/cinemas/detail/{slug}`
* **Mô tả**: Lấy thông tin chi tiết rạp để Next.js render trang `/cinemas/[slug]` và `generateMetadata()`.
* **Hỗ trợ tương thích ngược**: Nếu truyền ID số (ví dụ `/cinemas/detail/1`), Backend tự động tìm theo `cinema_id`.
* **Fields quan trọng cho SEO**:
  * `name` / `cinema_name`: Tên rạp (`og:title`, Schema.org `MovieTheater`).
  * `address` / `cinema_address`: Địa chỉ chi tiết (Schema.org `PostalAddress`).
  * `province` / `city`: Thành phố/tỉnh thành (Schema.org `addressLocality`).
  * `phone`: Hotline liên hệ (Schema.org `telephone`).
  * `description`: Mô tả rạp (`meta description`).
  * `isActive`: Trạng thái rạp.
  * `updated_at`: Thời gian cập nhật rạp.

### 2.3. Sitemap Feed (Tối ưu hóa tuyệt đối cho Next.js)

Next.js `app/sitemap.ts` cần lấy danh sách URL public cùng `lastModified`. Backend cung cấp 3 endpoint chuyên dụng siêu nhẹ, có cache:

#### A. Unified Sitemap (Tất cả trong 1 request - Khuyên dùng)
* **Endpoint**: `GET /api/v1/sitemap`
* **Mô tả**: Trả về danh sách public movies (chỉ `now_showing`, `upcoming`) và active cinemas (`is_active = true`).
* **Cache**: 3600 giây (tự động xóa cache ngay khi Admin thêm/sửa/đổi trạng thái phim hoặc rạp).
* **Sample Response**:
```json
{
  "success": true,
  "data": {
    "movies": [
      {
        "slug": "cai-chet-cua-robin-hood",
        "title": "Cái Chết của Robin Hood",
        "status": "now_showing",
        "updated_at": "2026-08-12T19:26:50.000000Z"
      }
    ],
    "cinemas": [
      {
        "slug": "cinedot-landmark-81",
        "name": "CineDot Landmark 81",
        "city": "TP. Hồ Chí Minh",
        "is_active": true,
        "updated_at": "2026-08-12T19:26:50.000000Z"
      }
    ]
  }
}
```

#### B. Movie Sitemap (Nếu muốn tách sitemap con)
* **Endpoint**: `GET /api/v1/sitemap/movies`

#### C. Cinema Sitemap (Nếu muốn tách sitemap con)
* **Endpoint**: `GET /api/v1/sitemap/cinemas`

---

## 3. Showtime & Booking (Nguyên tắc Realtime)

* **Endpoints**:
  * `GET /api/v1/showtimes?date=YYYY-MM-DD`
  * `GET /api/v1/movies/{identifier}/showtimes`
  * `GET /api/v1/cinemas/detail/{slug}/showtimes?date=YYYY-MM-DD`
  * `GET /api/v1/showtimes/{id}/seat-status`
* **Nguyên tắc SEO & Performance**:
  * Các endpoint lịch chiếu và trạng thái ghế **tuyệt đối không bị cache tĩnh**, dữ liệu trả về realtime theo từng suất chiếu.
  * Trong `GET /api/v1/cinemas/detail/{slug}/showtimes`, backend đã bổ sung `withCount('showtimeSeats')` để trường `availableSeats` trả về chính xác số ghế trống thực tế.

---

## 4. Code Mẫu Triển Khai Next.js (App Router)

### 4.1. File `app/sitemap.ts`
```typescript
import { MetadataRoute } from 'next';

interface SitemapResponse {
  success: boolean;
  data: {
    movies: Array<{ slug: string; updated_at: string }>;
    cinemas: Array<{ slug: string; updated_at: string }>;
  };
}

export default async function sitemap(): Promise<MetadataRoute.Sitemap> {
  const baseUrl = process.env.NEXT_PUBLIC_SITE_URL || 'https://cinedot.vn';

  try {
    const res = await fetch(`${process.env.NEXT_PUBLIC_API_URL}/api/v1/sitemap`, {
      next: { revalidate: 3600 },
    });
    const json: SitemapResponse = await res.json();

    const movieEntries: MetadataRoute.Sitemap = (json.data?.movies || []).map((m) => ({
      url: `${baseUrl}/movies/${m.slug}`,
      lastModified: new Date(m.updated_at),
      changeFrequency: 'daily',
      priority: 0.8,
    }));

    const cinemaEntries: MetadataRoute.Sitemap = (json.data?.cinemas || []).map((c) => ({
      url: `${baseUrl}/cinemas/${c.slug}`,
      lastModified: new Date(c.updated_at),
      changeFrequency: 'weekly',
      priority: 0.7,
    }));

    return [
      {
        url: baseUrl,
        lastModified: new Date(),
        changeFrequency: 'hourly',
        priority: 1.0,
      },
      ...movieEntries,
      ...cinemaEntries,
    ];
  } catch (error) {
    console.error('Error generating sitemap:', error);
    return [
      {
        url: baseUrl,
        lastModified: new Date(),
        changeFrequency: 'hourly',
        priority: 1.0,
      },
    ];
  }
}
```

### 4.2. File `app/movies/[slug]/page.tsx` (`generateMetadata`)
```typescript
import { Metadata } from 'next';

interface Props {
  params: { slug: string };
}

export async function generateMetadata({ params }: Props): Promise<Metadata> {
  const res = await fetch(`${process.env.NEXT_PUBLIC_API_URL}/api/v1/movies/${params.slug}`);
  const { data: movie } = await res.json();

  if (!movie) {
    return { title: 'Phim không tồn tại - CineDot' };
  }

  const title = `${movie.title} - Lịch chiếu & Đặt vé | CineDot`;
  const description = movie.overview || `Đặt vé xem phim ${movie.title} tại CineDot cụm rạp chuẩn quốc tế.`;

  return {
    title,
    description,
    openGraph: {
      title,
      description,
      type: 'video.movie',
      url: `https://cinedot.vn/movies/${movie.slug}`,
      images: [
        {
          url: movie.backdropUrl || movie.posterUrl,
          width: 1200,
          height: 630,
          alt: movie.title,
        },
      ],
    },
    alternates: {
      canonical: `https://cinedot.vn/movies/${movie.slug}`,
    },
  };
}
```
