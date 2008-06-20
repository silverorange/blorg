create or replace view BlorgPostCommentCountView as
	select post, count(id) as comment_count,
			max(createdate) as last_comment_date
		from BlorgComment
		where spam = false
		group by post;
